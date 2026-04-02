<?php

namespace App\Http\Controllers;

use App\Models\EmergencyReport;
use App\Events\EmergencyLocationSend;
use Illuminate\Http\Request;
use App\Events\ResponderLocationSend;
use App\Models\EmergencyResponderLocationLog;
use App\Models\EmergencyReportResponder;
use App\Models\ResourceUsageLog;
use App\Notifications\EmergencyReportStatusChanged;
use App\Notifications\ResponderStatusChanged;
use App\Events\ResponderNotificationSent;

class ResponderController extends Controller
{
    public function show($id)
    {
        $report = EmergencyReport::where('id', $id)
            ->firstOrFail();
        
        // Check if report is cancelled - cannot access cancelled reports
        if ($report->is_cancelled) {
            abort(404, 'This report has been cancelled and is no longer accessible.');
        }

        // Check if report is resolved (based on primary responder status)
        $primaryResponder = $report->responders()->where('role', 'primary')->first();
        if ($primaryResponder && $primaryResponder->status === 'completed') {
            abort(404, 'This report has been resolved.');
        }

        if ($report) {
            $user = auth()->user() ?? null;
            
            // Check if responder can respond to this emergency type
            if ($user && $user->isResponder()) {
                $user->load('responderDetail');
                $responderDetail = $user->responderDetail;
                
                // Check if responder has any emergency types selected
                if (!$responderDetail || !$responderDetail->emergency_types || count($responderDetail->emergency_types) === 0) {
                    return redirect()
                        ->route('reports.map')
                        ->with('error', 'You must select at least one emergency type in your profile before you can respond to reports. Please update your profile first.')
                        ->with('error_title', 'No Emergency Types Selected');
                }
                
                // Check if responder has the specific emergency type for this report
                if (!in_array($report->type, $responderDetail->emergency_types)) {
                    return redirect()
                        ->route('reports.map')
                        ->with('error', 'You are not qualified to respond to this type of emergency. Please update your profile to include this emergency type.')
                        ->with('error_title', 'Not Qualified');
                }
            }
            
            // Check if the user already has a pending response as PRIMARY responder
            // Allow multiple secondary responder assignments
            $pendingPrimaryResponse = EmergencyReportResponder::where('responder_id', $user->id)
                ->where('role', 'primary')
                ->whereHas('emergencyReport', function($q) {
                    $q->whereDoesntHave('responders', function($subQ) {
                        $subQ->where('role', 'primary')
                             ->whereIn('status', ['completed', 'cancelled']);
                    });
                })
                ->first();
    
            if ($pendingPrimaryResponse && $pendingPrimaryResponse->emergency_report_id != $id) {
                return redirect()
                    ->route('reports.map')
                    ->with([
                        'error' => 'You already have a pending primary response. You can only be the primary responder for one report at a time. You can still join other reports as a secondary responder.',
                        'pending_url' => route('response.show', $pendingPrimaryResponse->emergency_report_id)
                    ]);
            }
        }
       
        // Get current user's responder assignment for this report
        $responderAssignment = null;
        if ($user && $user->isResponder()) {
            $responderAssignment = EmergencyReportResponder::where('emergency_report_id', $report->id)
                ->where('responder_id', $user->id)
                ->first();
        }
        
        return view('response', compact('report', 'responderAssignment'));
    }

    public function send(Request $request)
    {
        $dataRes = $request->input();

        $validated = $request->validate([
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'message' => 'nullable|string',
            'summary' => 'nullable|string',
            'report_id' => 'required|exists:emergency_reports,id',
            'responder_id' => 'required|exists:users,id',
            'responder_name' => 'required|string|max:255',
            'severity_level' => 'nullable|string|max:50',
        ]);
        
                // Get responder role and status from emergency_report_responders table
                $responderAssignment = EmergencyReportResponder::where('emergency_report_id', $validated['report_id'])
                    ->where('responder_id', $validated['responder_id'])
                    ->first();
                
                $role = 'secondary'; // default
                $status = null;
                if ($responderAssignment) {
                    $role = $responderAssignment->role;
                    $status = $responderAssignment->status;
                }
        
        // Only broadcast location if responder status is 'en_route' or 'on_scene'
        if ($status && in_array($status, ['en_route', 'on_scene'])) {
            // Add role and status to broadcast data
            $dataRes['responder_role'] = $role;
            $dataRes['responder_status'] = $status;
            broadcast(new ResponderLocationSend($dataRes))->toOthers();
        }
        
        $response = EmergencyResponderLocationLog::create([
            'report_id'      => $validated['report_id'],
            'responder_id'   => $validated['responder_id'],
            'responder_name' => $validated['responder_name'],
            'message'        => $validated['message'] ?? null,
            'summary'        => $validated['summary'] ?? null,
            'latitude'            => $validated['latitude'] ?? null,
            'longitude'            => $validated['longitude'] ?? null,
            'severity_level'     => $validated['severity_level'] ?? null,
        ])->fresh();

        if(!$response) {
            return response()->json(['success' => false], 500);
        }

        return response()->json(['success' => true]);

        // $report = EmergencyReport::find($validated['report_id']);
        // if($report) {

        //     if ($report->status === 'acknowledged') {
        //         $report->status = 'dispatched';
        //     }
           
        //     $report->dispatched_at = now();
        //     $report->save();

        //     $data = $report->toArray();
        //     broadcast(new EmergencyLocationSend($data))->toOthers();

        //     return response()->json(['success' => true]);
        // } else {
        //     return response()->json(['success' => false], 404);
        // }
    }

    public function respond(Request $request)
    {
        $validated = $request->validate([
            'report_id' => 'required|integer',
            'responder_id' => 'required|integer',
            'response_notes' => 'nullable|string|max:1000',
            'responding_unit' => 'nullable|string|max:255',
            'type_of_response' => 'nullable|string|max:255',
        ]);

        $report = EmergencyReport::find($validated['report_id']);

        if ($report) {
            $typeOfResponse = $validated['type_of_response'] ?? 'null';

            if ($typeOfResponse === 'null') {
                return response()->json(['status' => 'error', 'message' => 'Type of response handling not implemented yet'] );
            }

            if ($typeOfResponse === 'response') {
                // Check if report is cancelled - cannot respond to cancelled reports
                if ($report->is_cancelled) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Cannot respond to a cancelled report.',
                    ], 422);
                }

                // Check if responder can respond to this emergency type
                $responder = \App\Models\User::with('responderDetail')->find($validated['responder_id']);
                if ($responder && $responder->isResponder() && $responder->responderDetail) {
                    // Check if responder status is available
                    if ($responder->responderDetail->status !== 'available') {
                        return response()->json([
                            'status' => 'error', 
                            'message' => 'You must be available to respond to reports. Please update your status to available in your profile.'
                        ]);
                    }
                    
                    $emergencyTypes = $responder->responderDetail->emergency_types ?? [];
                    
                    // Check if responder has no emergency types selected
                    if (count($emergencyTypes) === 0) {
                        return response()->json([
                            'status' => 'error', 
                            'message' => 'You must select at least one emergency type in your profile before you can respond to reports. Please update your profile first.'
                        ]);
                    }
                    
                    // Check if responder has the specific emergency type for this report
                    if (!in_array($report->type, $emergencyTypes)) {
                        return response()->json([
                            'status' => 'error', 
                            'message' => 'You are not qualified to respond to this type of emergency. Please update your profile to include this emergency type.'
                        ]);
                    }
                } elseif ($responder && $responder->isResponder() && !$responder->responderDetail) {
                    // Responder has no responder detail record
                    return response()->json([
                        'status' => 'error', 
                        'message' => 'You must select at least one emergency type in your profile before you can respond to reports. Please update your profile first.'
                    ]);
                }

                $responderId = $validated['responder_id'];

                // Allow secondary responders even if primary responder is assigned
                // Check if responder is already assigned (primary or secondary)
                $existingAssignment = EmergencyReportResponder::where('emergency_report_id', $report->id)
                    ->where('responder_id', $responderId)
                    ->first();
                
                // Check if there's already a primary responder
                $hasPrimaryResponder = $report->responders()->where('role', 'primary')->exists();
                $isPrimaryResponder = $existingAssignment && $existingAssignment->role === 'primary';
                
                if ($existingAssignment) {
                    // Already assigned, just update status, notes, and responding_unit
                    $existingAssignment->status = 'assigned';
                    if (!$existingAssignment->assigned_at) {
                        $existingAssignment->assigned_at = now();
                    }
                    $existingAssignment->response_notes = $validated['response_notes'] ?? $existingAssignment->response_notes;
                    
                    $responder = \App\Models\User::with('responderDetail')->find($responderId);
                    $existingAssignment->responding_unit = $responder->responderDetail->department ?? $validated['responding_unit'] ?? $existingAssignment->responding_unit;
                    $existingAssignment->save();
                    
                    // Update responder status to busy
                    if ($responder->responderDetail) {
                        $responder->responderDetail->update(['status' => 'busy']);
                    }
                    
                    // Timestamps are now managed at the responder assignment level
                } else {
                    // New assignment
                    $responder = \App\Models\User::with('responderDetail')->find($responderId);
                    $respondingUnit = $responder->responderDetail->department ?? $validated['responding_unit'] ?? null;
                    
                    if ($hasPrimaryResponder && !$isPrimaryResponder) {
                        // Primary responder exists, assign as secondary
                        // Don't change report status
                        // Just create the secondary responder assignment
                        EmergencyReportResponder::create([
                            'emergency_report_id' => $report->id,
                            'responder_id' => $responderId,
                            'role' => 'secondary',
                            'status' => 'assigned',
                            'assigned_at' => now(),
                            'response_notes' => $validated['response_notes'] ?? null,
                            'responding_unit' => $respondingUnit,
                        ]);
                        
                        // Update responder status to busy
                        if ($responder->responderDetail) {
                            $responder->responderDetail->update(['status' => 'busy']);
                        }
                    } else {
                        // No primary responder, this becomes the primary
                        // Check if report already has any active responders (shouldn't happen, but safety check)
                        if ($report->responders()->whereNotIn('status', ['cancelled', 'completed'])->exists()) {
                            return response()->json(['status' => 'error', 'message' => 'This report already has an active responder.'] );
                        }
                        
                        // Create primary responder assignment
                        $primaryAssignment = EmergencyReportResponder::create([
                            'emergency_report_id' => $report->id,
                            'responder_id' => $responderId,
                            'role' => 'primary',
                            'status' => 'assigned',
                            'assigned_at' => now(),
                            'response_notes' => $validated['response_notes'] ?? null,
                            'responding_unit' => $respondingUnit,
                        ]);
                        
                        // Update responder status to busy
                        if ($responder->responderDetail) {
                            $responder->responderDetail->update(['status' => 'busy']);
                        }
                    }
                }

                // Get old status before refresh
                $oldStatus = $report->status ?? 'pending';

                // Refresh report relationships to get latest data
                $report->refresh();
                $report->load(['responders.responder']);
                
                // Get the responder assignment that just responded (either existing or newly created)
                $responderAssignment = EmergencyReportResponder::where('emergency_report_id', $report->id)
                    ->where('responder_id', $responderId)
                    ->with('responder')
                    ->first();
                
                // Add calculated status to broadcast data
                $data = $report->toArray();
                
                // When a responder responds, base the status on THEIR status, not the primary responder's
                // This ensures the broadcast reflects the action of the responder who just responded
                if ($responderAssignment) {
                    // Map the current responder's status to report status
                    $statusMap = [
                        'assigned' => 'acknowledged',
                        'en_route' => 'dispatched',
                        'on_scene' => 'in_progress',
                        'completed' => 'resolved',
                        'cancelled' => 'cancelled',
                    ];
                    // Use the responder's current status (should be 'assigned' since they just responded)
                    $data['status'] = $statusMap[$responderAssignment->status] ?? 'pending';
                    
                    // Add responder information to broadcast data
                    $data['responder_name'] = $responderAssignment->responder->name ?? null;
                    $data['responder_role'] = $responderAssignment->role ?? null;
                } else {
                    // Fallback - shouldn't happen, but just in case
                    $data['status'] = 'pending';
                }
                
                broadcast(new EmergencyLocationSend($data))->toOthers();

                // Send notification to report owner after status is updated
                if ($report->user) {
                    $report->user->notify(new EmergencyReportStatusChanged($report, $oldStatus, 'acknowledged'));
                }

                // Notify other responders that this responder joined
                if ($responderAssignment) {
                    $joiningResponder = $responderAssignment->responder;
                    $role = $responderAssignment->role;
                    
                    // Get all other responders on this report
                    $otherResponders = EmergencyReportResponder::where('emergency_report_id', $report->id)
                        ->where('responder_id', '!=', $responderId)
                        ->whereNotIn('status', ['cancelled', 'completed'])
                        ->with('responder')
                        ->get();
                    
                    // Notify each other responder
                    foreach ($otherResponders as $otherAssignment) {
                        if ($otherAssignment->responder) {
                            // Send database notification
                            $notification = new ResponderStatusChanged($report, $joiningResponder, 'joined', $role);
                            $otherAssignment->responder->notify($notification);
                            
                            // Broadcast real-time notification
                            $notificationData = [
                                'report_id' => $report->id,
                                'responder_id' => $joiningResponder->id,
                                'responder_name' => $joiningResponder->name,
                                'action' => 'joined',
                                'role' => $role,
                                'message' => "{$joiningResponder->name} (" . ($role === 'primary' ? 'Primary' : 'Secondary') . " Responder) has joined as Report #{$report->id}.",
                                'type' => 'info',
                            ];
                            broadcast(new ResponderNotificationSent($notificationData, $otherAssignment->responder->id))->toOthers();
                        }
                    }
                }

                return response()->json(['status' => 'success']);
            }

            if ($typeOfResponse === 'dispatch') {
                // Check if report is cancelled - cannot dispatch for cancelled reports
                if ($report->is_cancelled) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Cannot dispatch for a cancelled report.',
                    ], 422);
                }

                // Check if responder is assigned (primary or secondary)
                $responderAssignment = EmergencyReportResponder::where('emergency_report_id', $report->id)
                    ->where('responder_id', $validated['responder_id'])
                    ->with('responder')
                    ->first();
                
                if (!$responderAssignment) {
                    return response()->json(['status' => 'error', 'message' => 'You are not assigned to this report.'] );
                }

                // Check if this responder has acknowledged (status should be 'assigned')
                if ($responderAssignment->status !== 'assigned') {
                    return response()->json(['status' => 'error', 'message' => 'You need to respond to the report first.'] );
                }

                // Get old status before update
                $oldStatus = $report->status ?? 'pending';

                // Update emergency_report_responders table for this responder
                $responderAssignment->status = 'en_route';
                $responderAssignment->en_route_at = now();
                $responderAssignment->save();

                // Add calculated status to broadcast data
                $data = $report->toArray();
                
                // Base status on the responder who is dispatching, not the primary responder
                // Map the current responder's status to report status
                $statusMap = [
                    'assigned' => 'acknowledged',
                    'en_route' => 'dispatched',
                    'on_scene' => 'in_progress',
                    'completed' => 'resolved',
                    'cancelled' => 'cancelled',
                ];
                $data['status'] = $statusMap[$responderAssignment->status] ?? 'pending';
                
                // Add responder information to broadcast data
                $data['responder_name'] = $responderAssignment->responder->name ?? null;
                $data['responder_role'] = $responderAssignment->role ?? null;
                
                broadcast(new EmergencyLocationSend($data))->toOthers();

                // Send notification to report owner after status is updated
                if ($report->user) {
                    $report->user->notify(new EmergencyReportStatusChanged($report, $oldStatus, 'dispatched'));
                }

                // Notify other responders that this responder dispatched
                $dispatchingResponder = $responderAssignment->responder;
                $role = $responderAssignment->role;
                
                // Get all other responders on this report
                $otherResponders = EmergencyReportResponder::where('emergency_report_id', $report->id)
                    ->where('responder_id', '!=', $validated['responder_id'])
                    ->whereNotIn('status', ['cancelled', 'completed'])
                    ->with('responder')
                    ->get();
                
                // Notify each other responder
                foreach ($otherResponders as $otherAssignment) {
                    if ($otherAssignment->responder) {
                        // Send database notification
                        $notification = new ResponderStatusChanged($report, $dispatchingResponder, 'dispatched', $role);
                        $otherAssignment->responder->notify($notification);
                        
                        // Broadcast real-time notification
                        $notificationData = [
                            'report_id' => $report->id,
                            'responder_id' => $dispatchingResponder->id,
                            'responder_name' => $dispatchingResponder->name,
                            'action' => 'dispatched',
                            'role' => $role,
                            'message' => "{$dispatchingResponder->name} (" . ($role === 'primary' ? 'Primary' : 'Secondary') . " Responder) has been dispatched Report #{$report->id}.",
                            'type' => 'info',
                        ];
                        broadcast(new ResponderNotificationSent($notificationData, $otherAssignment->responder->id))->toOthers();
                    }
                }

                return response()->json(['status' => 'success']);
            }

            if ($typeOfResponse === 'arrival') {
                // Check if report is cancelled - cannot mark arrival for cancelled reports
                if ($report->is_cancelled) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Cannot mark arrival for a cancelled report.',
                    ], 422);
                }

                // Check if responder is assigned (primary or secondary)
                $responderAssignment = EmergencyReportResponder::where('emergency_report_id', $report->id)
                    ->where('responder_id', $validated['responder_id'])
                    ->with('responder')
                    ->first();
                
                if (!$responderAssignment) {
                    return response()->json(['status' => 'error', 'message' => 'You are not assigned to this report.'] );
                }

                // Check if this responder has dispatched (status should be 'en_route')
                if ($responderAssignment->status !== 'en_route') {
                    return response()->json(['status' => 'error', 'message' => 'You need to dispatch first before marking as on scene.'] );
                }

                // Get old status before update
                $oldStatus = $report->status ?? 'pending';

                // Update emergency_report_responders table for this responder
                $responderAssignment->status = 'on_scene';
                $responderAssignment->on_scene_at = now();
                $responderAssignment->save();

                // Add calculated status to broadcast data
                $data = $report->toArray();
                
                // Base status on the responder who is arriving, not the primary responder
                // Map the current responder's status to report status
                $statusMap = [
                    'assigned' => 'acknowledged',
                    'en_route' => 'dispatched',
                    'on_scene' => 'in_progress',
                    'completed' => 'resolved',
                    'cancelled' => 'cancelled',
                ];
                $data['status'] = $statusMap[$responderAssignment->status] ?? 'pending';
                
                // Add responder information to broadcast data
                $data['responder_name'] = $responderAssignment->responder->name ?? null;
                $data['responder_role'] = $responderAssignment->role ?? null;
                
                broadcast(new EmergencyLocationSend($data))->toOthers();

                // Send notification to report owner after status is updated
                if ($report->user) {
                    $report->user->notify(new EmergencyReportStatusChanged($report, $oldStatus, 'in_progress'));
                }

                // Notify other responders that this responder arrived
                $arrivingResponder = $responderAssignment->responder;
                $role = $responderAssignment->role;
                
                // Get all other responders on this report
                $otherResponders = EmergencyReportResponder::where('emergency_report_id', $report->id)
                    ->where('responder_id', '!=', $validated['responder_id'])
                    ->whereNotIn('status', ['cancelled', 'completed'])
                    ->with('responder')
                    ->get();
                
                // Notify each other responder
                foreach ($otherResponders as $otherAssignment) {
                    if ($otherAssignment->responder) {
                        // Send database notification
                        $notification = new ResponderStatusChanged($report, $arrivingResponder, 'arrived', $role);
                        $otherAssignment->responder->notify($notification);
                        
                        // Broadcast real-time notification
                        $notificationData = [
                            'report_id' => $report->id,
                            'responder_id' => $arrivingResponder->id,
                            'responder_name' => $arrivingResponder->name,
                            'action' => 'arrived',
                            'role' => $role,
                            'message' => "{$arrivingResponder->name} (" . ($role === 'primary' ? 'Primary' : 'Secondary') . " Responder) has arrived on scene Report #{$report->id}.",
                            'type' => 'info',
                        ];
                        broadcast(new ResponderNotificationSent($notificationData, $otherAssignment->responder->id))->toOthers();
                    }
                }

                return response()->json(['status' => 'success']);
            }

            if ($typeOfResponse === 'resolve') {
                // Check if report is cancelled - cannot resolve cancelled reports
                if ($report->is_cancelled) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Cannot resolve a cancelled report.',
                    ], 422);
                }

                // Check if responder is assigned (primary or secondary)
                $responderAssignment = EmergencyReportResponder::where('emergency_report_id', $report->id)
                    ->where('responder_id', $validated['responder_id'])
                    ->with('responder')
                    ->first();
                
                if (!$responderAssignment) {
                    return response()->json(['status' => 'error', 'message' => 'You are not assigned to this report.'] );
                }

                // Only primary responder can resolve the report
                if ($responderAssignment->role !== 'primary') {
                    return response()->json(['status' => 'error', 'message' => 'Only the primary responder can resolve this report.'] );
                }

                // Check if responder is on scene
                if ($responderAssignment->status !== 'on_scene') {
                    return response()->json(['status' => 'error', 'message' => 'You need to be on scene to resolve.'] );
                }

                // Get old status before update
                $oldStatus = $report->status ?? 'pending';

                // Primary responder resolves - update all responder statuses
                $completedResponders = EmergencyReportResponder::where('emergency_report_id', $report->id)
                    ->where('status', '!=', 'cancelled')
                    ->with('responder.responderDetail')
                    ->get();
                
                EmergencyReportResponder::where('emergency_report_id', $report->id)
                    ->where('status', '!=', 'cancelled')
                    ->update([
                        'status' => 'completed',
                        'completed_at' => now()
                    ]);

                // Update all responder statuses to available
                foreach ($completedResponders as $assignment) {
                    if ($assignment->responder && $assignment->responder->responderDetail) {
                        $assignment->responder->responderDetail->update(['status' => 'available']);
                    }
                }

                // Update all resources used for this report - set them back to available
                $resourcesUsed = \App\Models\Resource::where('current_report_id', $report->id)
                    ->where('status', 'in_use')
                    ->get();

                foreach ($resourcesUsed as $resource) {
                    // Update resource status back to available
                    $resource->update([
                        'current_report_id' => null,
                        'status' => 'available',
                        'assigned_to' => null,
                    ]);

                    // Find existing log entry
                    $existingLog = ResourceUsageLog::where('emergency_report_id', $report->id)
                        ->where('resource_id', $resource->id)
                        ->first();

                    if ($existingLog) {
                        // Update existing log - preserve assigned_at, only update released_at
                        $existingLog->update([
                            'released_at' => now(),
                            'notes' => 'Resource used during emergency response - Report resolved',
                        ]);
                    } else {
                        // Create new log entry if it doesn't exist (shouldn't happen, but safety check)
                        ResourceUsageLog::create([
                            'emergency_report_id' => $report->id,
                            'resource_id' => $resource->id,
                            'responder_id' => $resource->assigned_to ?? $resource->responder_id,
                            'assigned_by' => $resource->assigned_to,
                            'assigned_at' => $resource->updated_at ?? now(),
                            'released_at' => now(),
                            'notes' => 'Resource used during emergency response - Report resolved',
                        ]);
                    }
                }

                // Add calculated status to broadcast data
                $data = $report->toArray();
                
                // Primary responder resolved - report status is resolved
                $data['status'] = 'resolved';
                
                // Add responder information to broadcast data
                $data['responder_name'] = $responderAssignment->responder->name ?? null;
                $data['responder_role'] = $responderAssignment->role ?? null;
                
                broadcast(new EmergencyLocationSend($data))->toOthers();

                // Send notification to report owner after status is updated
                if ($report->user) {
                    $report->user->notify(new EmergencyReportStatusChanged($report, $oldStatus, 'resolved'));
                }

                // Notify other responders that primary responder resolved the report
                $resolvingResponder = $responderAssignment->responder;
                $role = $responderAssignment->role;
                
                // Get all other responders on this report (before they're marked as completed)
                $otherResponders = EmergencyReportResponder::where('emergency_report_id', $report->id)
                    ->where('responder_id', '!=', $validated['responder_id'])
                    ->whereNotIn('status', ['cancelled'])
                    ->with('responder')
                    ->get();
                
                // Notify each other responder
                foreach ($otherResponders as $otherAssignment) {
                    if ($otherAssignment->responder) {
                        // Send database notification
                        $notification = new ResponderStatusChanged($report, $resolvingResponder, 'resolved', $role);
                        $otherAssignment->responder->notify($notification);
                        
                        // Broadcast real-time notification
                        $notificationData = [
                            'report_id' => $report->id,
                            'responder_id' => $resolvingResponder->id,
                            'responder_name' => $resolvingResponder->name,
                            'action' => 'resolved',
                            'role' => $role,
                            'message' => "{$resolvingResponder->name} (" . ($role === 'primary' ? 'Primary' : 'Secondary') . " Responder) has resolved Report #{$report->id}.",
                            'type' => 'success',
                        ];
                        broadcast(new ResponderNotificationSent($notificationData, $otherAssignment->responder->id))->toOthers();
                    }
                }

                return response()->json(['status' => 'success']);
            }
            
            if ($typeOfResponse === 'cancel') {
                // Check if responder is assigned (primary or secondary)
                $responderAssignment = EmergencyReportResponder::where('emergency_report_id', $report->id)
                    ->where('responder_id', $validated['responder_id'])
                    ->with('responder')
                    ->first();
                
                if (!$responderAssignment) {
                    return response()->json(['status' => 'error', 'message' => 'You are not assigned to this report.'] );
                }

                // Check if report is already resolved or cancelled
                if ($responderAssignment->status === 'completed' || $responderAssignment->status === 'cancelled') {
                    return response()->json(['status' => 'error', 'message' => 'This report is already resolved or cancelled.'] );
                }

                // Primary responders cannot cancel their response
                if ($responderAssignment->role === 'primary') {
                    return response()->json(['status' => 'error', 'message' => 'Primary responders cannot cancel their response.'] );
                }

                // Only secondary responders can cancel their response
                $responderAssignment->status = 'cancelled';
                $responderAssignment->cancelled_at = now();
                $responderAssignment->save();

                // Update responder status to available
                if ($responderAssignment->responder && $responderAssignment->responder->responderDetail) {
                    $responderAssignment->responder->responderDetail->update(['status' => 'available']);
                }

                // Add calculated status to broadcast data
                $data = $report->toArray();
                
                // Secondary responder cancelled - remove only this responder's marker
                $data['cancelled_responder_id'] = $validated['responder_id'];
                // When secondary responder cancels, use the report's actual status (based on primary)
                $data['status'] = $report->status;
                
                // Add responder information to broadcast data
                $data['responder_name'] = $responderAssignment->responder->name ?? null;
                $data['responder_role'] = $responderAssignment->role ?? null;
                
                broadcast(new EmergencyLocationSend($data))->toOthers();

                // Notify other responders and report owner that this secondary responder cancelled
                $cancellingResponder = $responderAssignment->responder;
                $role = $responderAssignment->role;
                
                // Prepare notification data
                $notificationData = [
                    'report_id' => $report->id,
                    'responder_id' => $cancellingResponder->id,
                    'responder_name' => $cancellingResponder->name,
                    'action' => 'cancelled',
                    'role' => $role,
                    'message' => "{$cancellingResponder->name} (" . ($role === 'primary' ? 'Primary' : 'Secondary') . " Responder) has cancelled their response Report #{$report->id}.",
                    'type' => 'warning',
                ];
                
                // Get all other responders on this report
                $otherResponders = EmergencyReportResponder::where('emergency_report_id', $report->id)
                    ->where('responder_id', '!=', $validated['responder_id'])
                    ->whereNotIn('status', ['cancelled', 'completed'])
                    ->with('responder')
                    ->get();
                
                // Notify each other responder
                foreach ($otherResponders as $otherAssignment) {
                    if ($otherAssignment->responder) {
                        // Send database notification
                        $notification = new ResponderStatusChanged($report, $cancellingResponder, 'cancelled', $role);
                        $otherAssignment->responder->notify($notification);
                        
                        // Broadcast real-time notification
                        broadcast(new ResponderNotificationSent($notificationData, $otherAssignment->responder->id))->toOthers();
                    }
                }
                
                // Also notify the report owner
                if ($report->user && $report->user->id != $validated['responder_id']) {
                    // Send database notification to report owner
                    $notification = new ResponderStatusChanged($report, $cancellingResponder, 'cancelled', $role);
                    $report->user->notify($notification);
                    
                    // Broadcast real-time notification to report owner
                    broadcast(new ResponderNotificationSent($notificationData, $report->user->id))->toOthers();
                }

                return response()->json(['status' => 'success']);
            }

            return response()->json(['status' => 'error', 'message' => 'Invalid type of response'] );
        }

        return response()->json(['status' => 'error', 'message' => 'Report not found']);
    }
}
