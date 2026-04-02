<?php

namespace App\Http\Controllers;

use App\Models\EmergencyReport;
use App\Models\EmergencyReportResponder;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Notifications\ResponderStatusChanged;
use App\Events\ResponderNotificationSent;

class MultiResponderController extends Controller
{
    public function assign(Request $request, $reportId)
    {
        $report = EmergencyReport::findOrFail($reportId);
        
        // Only admins and responders can assign
        if (!Auth::user()->isAdmin() && !Auth::user()->isResponder()) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'responder_id' => 'required|exists:users,id',
            'role' => 'required|in:primary,secondary,support',
            'response_notes' => 'nullable|string',
            'responding_unit' => 'nullable|string',
        ]);

        // Check if the user is actually a responder (type = 1)
        $responderUser = User::find($validated['responder_id']);
        if (!$responderUser || !$responderUser->isResponder()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Only users with responder type can be assigned to reports.',
                'errors' => ['responder_id' => ['The selected user must be a responder.']]
            ], 422);
        }

        // Check if responder is already assigned
        $existing = EmergencyReportResponder::where('emergency_report_id', $reportId)
            ->where('responder_id', $validated['responder_id'])
            ->first();

        if ($existing) {
            return response()->json([
                'status' => 'error',
                'message' => 'Responder is already assigned to this report',
            ], 400);
        }

        // Check if there's already a primary responder
        $hasPrimary = EmergencyReportResponder::where('emergency_report_id', $reportId)
            ->where('role', 'primary')
            ->exists();

        // If assigning as primary and one already exists, change existing to secondary
        if ($validated['role'] === 'primary' && $hasPrimary) {
            EmergencyReportResponder::where('emergency_report_id', $reportId)
                ->where('role', 'primary')
                ->update(['role' => 'secondary']);
        }

        $responder = User::with('responderDetail')->find($validated['responder_id']);
        $respondingUnit = $responder->responderDetail->department ?? $validated['responding_unit'] ?? null;

        $assignment = EmergencyReportResponder::create([
            'emergency_report_id' => $reportId,
            'responder_id' => $validated['responder_id'],
            'role' => $validated['role'],
            'status' => 'assigned',
            'assigned_at' => now(),
            'response_notes' => $validated['response_notes'] ?? null,
            'responding_unit' => $respondingUnit,
        ]);

        // Update responder status to busy
        if ($responder->responderDetail) {
            $responder->responderDetail->update(['status' => 'busy']);
        }

        // Reload assignment with relationships
        $assignment->load('responder');
        
        // Send database notification to the assigned responder
        $notification = new ResponderStatusChanged($report, $responder, 'assigned', $validated['role']);
        $responder->notify($notification);
        
        // Broadcast real-time notification to the assigned responder
        $roleLabel = $validated['role'] === 'primary' ? 'Primary' : ($validated['role'] === 'secondary' ? 'Secondary' : 'Support');
        $notificationData = [
            'report_id' => $report->id,
            'report_type' => $report->type,
            'report_address' => $report->address,
            'report_severity' => $report->severity_level,
            'assigned_by_id' => Auth::id(),
            'assigned_by_name' => Auth::user()->name,
            'action' => 'assigned',
            'role' => $validated['role'],
            'role_label' => $roleLabel,
            'message' => "You have been assigned as {$roleLabel} Responder to Report #{$report->id} ({$report->type}).",
            'type' => 'info',
            'title' => 'New Assignment',
        ];
        broadcast(new ResponderNotificationSent($notificationData, $responder->id))->toOthers();

        return response()->json([
            'status' => 'success',
            'message' => 'Responder assigned successfully',
            'assignment' => $assignment,
        ]);
    }

    public function index($reportId)
    {
        $report = EmergencyReport::findOrFail($reportId);
        
        $responders = $report->responders()->with('responder.responderDetail')->get();

        return response()->json([
            'status' => 'success',
            'responders' => $responders,
        ]);
    }

    public function updateStatus(Request $request, $assignmentId)
    {
        $assignment = EmergencyReportResponder::findOrFail($assignmentId);
        
        // Only the assigned responder or admin can update status
        if ($assignment->responder_id !== Auth::id() && !Auth::user()->isAdmin()) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'status' => 'required|in:assigned,en_route,on_scene,completed,cancelled',
        ]);

        $oldStatus = $assignment->status;
        $assignment->status = $validated['status'];

        // Update timestamps based on status
        switch ($validated['status']) {
            case 'en_route':
                $assignment->en_route_at = now();
                break;
            case 'on_scene':
                $assignment->on_scene_at = now();
                break;
            case 'completed':
                $assignment->completed_at = now();
                break;
        }

        $assignment->save();

        // Update responder status to available if assignment is completed or cancelled
        if (in_array($validated['status'], ['completed', 'cancelled'])) {
            $assignment->load('responder.responderDetail');
            if ($assignment->responder && $assignment->responder->responderDetail) {
                $assignment->responder->responderDetail->update(['status' => 'available']);
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Responder status updated',
            'assignment' => $assignment,
        ]);
    }

    public function remove($assignmentId)
    {
        $assignment = EmergencyReportResponder::findOrFail($assignmentId);
        
        // Only admin can remove assignments
        if (!Auth::user()->isAdmin()) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        // Update responder status to available before removing assignment
        $assignment->load('responder.responderDetail');
        if ($assignment->responder && $assignment->responder->responderDetail) {
            $assignment->responder->responderDetail->update(['status' => 'available']);
        }

        $assignment->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Responder assignment removed',
        ]);
    }
}

