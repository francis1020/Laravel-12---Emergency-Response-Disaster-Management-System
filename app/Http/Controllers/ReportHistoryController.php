<?php

namespace App\Http\Controllers;

use App\Models\EmergencyReport;
use App\Models\EmergencyType;
use App\Models\User;
use App\Models\EmergencyReportResponder;
use App\Events\EmergencyLocationSend;
use App\Events\ResponderNotificationSent;
use App\Notifications\EmergencyReportStatusChanged;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ReportHistoryController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        
        $query = EmergencyReport::query();

        // Filter based on user type
        if ($user->isResponder()) {
            $reportIds = \App\Models\EmergencyReportResponder::where('responder_id', $user->id)
                ->pluck('emergency_report_id');
            $query->whereIn('id', $reportIds);
        } elseif ($user->isUser()) {
            $query->where('user_id', $user->id);
        }
        // Admins see all reports

        // Apply filters
        if ($request->filled('status')) {
            // Map report status to responder status
            $statusMap = [
                'pending' => function($q) {
                    $q->whereDoesntHave('responders');
                },
                'acknowledged' => function($q) {
                    $q->whereHas('responders', function($subQ) {
                        $subQ->where('role', 'primary')->where('status', 'assigned');
                    });
                },
                'dispatched' => function($q) {
                    $q->whereHas('responders', function($subQ) {
                        $subQ->where('role', 'primary')->where('status', 'en_route');
                    });
                },
                'in_progress' => function($q) {
                    $q->whereHas('responders', function($subQ) {
                        $subQ->where('role', 'primary')->where('status', 'on_scene');
                    });
                },
                'resolved' => function($q) {
                    $q->whereHas('responders', function($subQ) {
                        $subQ->where('role', 'primary')->where('status', 'completed');
                    });
                },
                'cancelled' => function($q) {
                    $q->whereHas('responders', function($subQ) {
                        $subQ->where('role', 'primary')->where('status', 'cancelled');
                    });
                },
            ];
            
            if (isset($statusMap[$request->status])) {
                $statusMap[$request->status]($query);
            }
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('severity')) {
            $query->where('severity_level', $request->severity);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%")
                  ->orWhere('contact_name', 'like', "%{$search}%")
                  ->orWhere('contact_number', 'like', "%{$search}%");
            });
        }

        $reports = $query->with(['user', 'emergencyType', 'responders.responder'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        // Get filter options
        $statuses = ['pending', 'acknowledged', 'dispatched', 'in_progress', 'resolved', 'cancelled'];
        $types = \App\Models\EmergencyType::where('status', 1)->get();
        $severities = ['low', 'moderate', 'high', 'critical'];

        return view('reports.history', compact('reports', 'statuses', 'types', 'severities'));
    }

    public function show($report_id)
    {
        $user = Auth::user();
        $report = EmergencyReport::with(['user', 'emergencyType', 'responders.responder.responderDetail'])->findOrFail($report_id);

        // Check access
        if ($user->isResponder()) {
            $isAssigned = \App\Models\EmergencyReportResponder::where('emergency_report_id', $report_id)
                ->where('responder_id', $user->id)
                ->exists();
            if (!$isAssigned) {
                abort(403, 'You do not have access to this report.');
            }
        } elseif ($user->isUser() && $report->user_id !== $user->id) {
            abort(403, 'You do not have access to this report.');
        }

        // Calculate response times from primary responder's timestamps
        $responseTimes = [];
        $primaryResponder = $report->responders()->where('role', 'primary')->first();
        
        if ($primaryResponder) {
            if ($primaryResponder->assigned_at && $report->reported_at) {
                $responseTimes['acknowledged'] = $report->reported_at->diffForHumans($primaryResponder->assigned_at, true);
            }
            if ($primaryResponder->en_route_at && $primaryResponder->assigned_at) {
                $responseTimes['dispatched'] = $primaryResponder->assigned_at->diffForHumans($primaryResponder->en_route_at, true);
            }
            if ($primaryResponder->on_scene_at && $primaryResponder->en_route_at) {
                $responseTimes['in_progress'] = $primaryResponder->en_route_at->diffForHumans($primaryResponder->on_scene_at, true);
            }
            if ($primaryResponder->completed_at) {
                if ($primaryResponder->on_scene_at) {
                    $responseTimes['resolved'] = $primaryResponder->on_scene_at->diffForHumans($primaryResponder->completed_at, true);
                } elseif ($report->reported_at) {
                    $responseTimes['resolved'] = $report->reported_at->diffForHumans($primaryResponder->completed_at, true);
                }
            }
        }

        // Get all responder assignments with their details
        $allResponders = $report->responders()->with('responder.responderDetail')->orderBy('role', 'desc')->orderBy('assigned_at', 'asc')->get();
        
        // Get resource usage logs for this report
        $resourceUsageLogs = $report->resourceUsageLogs()->with(['resource', 'responder', 'assignedBy'])->orderBy('assigned_at', 'desc')->get();
        
        // Get unread message count for this report (only messages not sent by current user)
        $unreadMessageCount = \App\Models\Message::where('emergency_report_id', $report_id)
            ->where('user_id', '!=', $user->id)
            ->whereDoesntHave('readBy', function($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->count();
        
        return view('reports.show', compact('report', 'responseTimes', 'allResponders', 'resourceUsageLogs', 'unreadMessageCount'));
    }

    public function update(Request $request, $report_id)
    {
        $user = Auth::user();
        $report = EmergencyReport::findOrFail($report_id);

        // Check if user owns the report OR is admin/super admin
        $canEdit = ($report->user_id === $user->id) || $user->isAdmin();
        if (!$canEdit) {
            abort(403, 'You do not have permission to edit this report.');
        }

        // Check if report can be edited (no responders assigned or status is pending)
        // Admins can always edit, regular users can only edit if no responders or pending
        $hasResponders = $report->responders()->exists();
        $isPending = $report->status === 'pending';
        
        if (!$user->isAdmin() && $hasResponders && !$isPending) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot edit report. Responders are already assigned and the report is not in pending status.',
            ], 422);
        }

        // Get valid emergency type codes from database
        $validTypeCodes = EmergencyType::where('status', true)->pluck('code')->toArray();
        
        $validated = $request->validate([
            'type' => [
                'required',
                'string',
                Rule::in($validTypeCodes),
            ],
            'severity_level' => [
                'required',
                Rule::in(['low', 'moderate', 'high', 'critical']),
            ],
            'description' => 'nullable|string|max:1000',
            'contact_name' => 'nullable|string|max:100|regex:/^[a-zA-Z\s\.\'-]+$/',
            'contact_number' => [
                'required',
                'regex:/^09\d{9}$/',
                'max:11'
            ],
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'address' => 'nullable|string|max:255',
        ]);

        // Update the report
        $report->update($validated);
        
        // Recalculate priority score (may have changed due to severity or location update)
        $report->priority_score = $report->calculatePriorityScore();
        $report->save();
        
        // Reload the report with relationships for broadcasting
        $report = $report->fresh(['user', 'emergencyType', 'responders.responder']);
        
        // Broadcast the updated report in real-time (same as new reports)
        $data = $report->toArray();
        broadcast(new EmergencyLocationSend($data))->toOthers();

        // Notify all responders whose emergency_types include this report type
        $responders = User::where('type', 1) // Only responders
            ->whereHas('responderDetail', function($query) use ($report) {
                $query->whereNotNull('emergency_types')
                      ->whereJsonContains('emergency_types', $report->type);
            })
            ->with('responderDetail')
            ->get();

        foreach ($responders as $responder) {
            // Send database notification
            $notification = new EmergencyReportStatusChanged($report, $report->status, 'updated');
            $responder->notify($notification);
            
            // Broadcast real-time notification
            $notificationData = [
                'report_id' => $report->id,
                'report_type' => $report->type,
                'report_address' => $report->address,
                'report_severity' => $report->severity_level,
                'old_status' => $report->status,
                'new_status' => 'updated',
                'message' => "Emergency report #{$report->id} ({$report->type}) has been updated.",
                'type' => 'info',
                'title' => 'Report Updated',
            ];
            broadcast(new ResponderNotificationSent($notificationData, $responder->id))->toOthers();
        }

        // Notify all admins and super admins (type = 2 or 3)
        $admins = User::whereIn('type', [2, 3])->get();

        foreach ($admins as $admin) {
            // Send database notification
            $notification = new EmergencyReportStatusChanged($report, $report->status, 'updated');
            $admin->notify($notification);
            
            // Broadcast real-time notification
            $notificationData = [
                'report_id' => $report->id,
                'report_type' => $report->type,
                'report_address' => $report->address,
                'report_severity' => $report->severity_level,
                'old_status' => $report->status,
                'new_status' => 'updated',
                'message' => "Emergency report #{$report->id} ({$report->type}) has been updated.",
                'type' => 'info',
                'title' => 'Report Updated',
            ];
            broadcast(new ResponderNotificationSent($notificationData, $admin->id))->toOthers();
        }

        // Also notify any assigned responders to this report
        $assignedResponders = $report->responders()->with('responder')->get();
        foreach ($assignedResponders as $assignment) {
            if ($assignment->responder) {
                $responder = $assignment->responder;
                // Skip if already notified above
                if (!$responders->contains('id', $responder->id) && !$admins->contains('id', $responder->id)) {
                    // Send database notification
                    $notification = new EmergencyReportStatusChanged($report, $report->status, 'updated');
                    $responder->notify($notification);
                    
                    // Broadcast real-time notification
                    $notificationData = [
                        'report_id' => $report->id,
                        'report_type' => $report->type,
                        'report_address' => $report->address,
                        'report_severity' => $report->severity_level,
                        'old_status' => $report->status,
                        'new_status' => 'updated',
                        'message' => "Emergency report #{$report->id} ({$report->type}) that you're assigned to has been updated.",
                        'type' => 'warning',
                        'title' => 'Assigned Report Updated',
                    ];
                    broadcast(new ResponderNotificationSent($notificationData, $responder->id))->toOthers();
                }
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Report updated successfully.',
            'report' => $report
        ]);
    }

    public function destroy($report_id)
    {
        $user = Auth::user();
        $report = EmergencyReport::findOrFail($report_id);

        // Check if user owns the report OR is admin/super admin
        $canDelete = ($report->user_id === $user->id) || $user->isAdmin();
        if (!$canDelete) {
            abort(403, 'You do not have permission to delete this report.');
        }

        // Check if there are responders assigned
        // Admins can always delete, regular users cannot delete if responders are assigned
        $hasResponders = $report->responders()->exists();
        if (!$user->isAdmin() && $hasResponders) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot delete report. There are responders assigned to this report. Please cancel or complete the response first.',
            ], 422);
        }

        // Store report data for broadcasting before deletion
        $reportData = $report->toArray();
        $reportId = $report->id;

        // Delete the report
        $report->delete();

        // Broadcast deletion event for real-time map updates
        $deleteData = [
            'id' => $reportId,
            'deleted' => true,
            'action' => 'delete'
        ];
        broadcast(new EmergencyLocationSend($deleteData))->toOthers();

        return response()->json([
            'status' => 'success',
            'message' => 'Report deleted successfully.',
        ]);
    }

    public function cancel($report_id)
    {
        $user = Auth::user();
        $report = EmergencyReport::findOrFail($report_id);

        // Check if user owns the report OR is admin/super admin
        $canCancel = ($report->user_id === $user->id) || $user->isAdmin();
        if (!$canCancel) {
            abort(403, 'You do not have permission to cancel this report.');
        }

        // Check if report is already cancelled
        if ($report->is_cancelled) {
            return response()->json([
                'status' => 'error',
                'message' => 'Report is already cancelled.',
            ], 422);
        }

        // Check if report is resolved (primary responder completed)
        $primaryResponder = $report->responders()->where('role', 'primary')->first();
        if ($primaryResponder && $primaryResponder->status === 'completed') {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot cancel a resolved report.',
            ], 422);
        }

        // Set report as cancelled
        $report->is_cancelled = true;
        $report->save();

        // Cancel all responder assignments (if any exist)
        $responderAssignments = EmergencyReportResponder::where('emergency_report_id', $report_id)
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->with('responder.responderDetail')
            ->get();

        foreach ($responderAssignments as $assignment) {
            $assignment->status = 'cancelled';
            $assignment->cancelled_at = now();
            $assignment->save();
            
            // Update responder status to available
            if ($assignment->responder && $assignment->responder->responderDetail) {
                $assignment->responder->responderDetail->update(['status' => 'available']);
            }
        }

        // Reload the report with relationships for broadcasting
        $report = $report->fresh(['user', 'emergencyType', 'responders.responder']);
        
        // Broadcast the cancellation in real-time
        $data = $report->toArray();
        $data['cancelled_responders'] = 'all'; // Indicate all responders were cancelled
        $data['status'] = 'cancelled';
        $data['is_cancelled'] = true; // Explicitly set the flag
        broadcast(new EmergencyLocationSend($data))->toOthers();

        // Notify all responders that the report was cancelled
        foreach ($responderAssignments as $assignment) {
            if ($assignment->responder) {
                // Send database notification
                $notification = new EmergencyReportStatusChanged($report, $report->status, 'cancelled');
                $assignment->responder->notify($notification);
                
                // Broadcast real-time notification
                $notificationData = [
                    'report_id' => $report->id,
                    'report_type' => $report->type,
                    'report_address' => $report->address,
                    'report_severity' => $report->severity_level,
                    'old_status' => $report->status,
                    'new_status' => 'cancelled',
                    'message' => "Emergency report #{$report->id} ({$report->type}) has been cancelled by " . ($user->isAdmin() ? 'an administrator' : 'the report owner') . ".",
                    'type' => 'warning',
                    'title' => 'Report Cancelled',
                ];
                broadcast(new ResponderNotificationSent($notificationData, $assignment->responder->id))->toOthers();
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Report cancelled successfully. All responder assignments have been cancelled.',
            'report' => $report
        ]);
    }
}

