<?php

namespace App\Http\Controllers;

use App\Models\Resource;
use App\Models\EmergencyReport;
use App\Models\ResourceUsageLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ResourceController extends Controller
{
    public function index(Request $request)
    {
        // Only admins and responders can view resources
        if (!Auth::user()->isAdmin() && !Auth::user()->isResponder()) {
            abort(403, 'Unauthorized');
        }

        $query = Resource::with(['assignedUser', 'currentReport', 'responder']);

        // For responders, only show their own resources. Admins see all resources.
        if (Auth::user()->isResponder() && !Auth::user()->isAdmin()) {
            $query->where('responder_id', Auth::id());
        }

        // Apply filters
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('identifier', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $resources = $query->paginate(12)->appends(request()->query());

        return view('resources.index', compact('resources'));
    }

    public function show($id)
    {
        // Only admins and responders can view resources
        if (!Auth::user()->isAdmin() && !Auth::user()->isResponder()) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $resource = Resource::with(['assignedUser', 'currentReport', 'responder'])->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'resource' => $resource,
        ]);
    }

    public function store(Request $request)
    {
        // Admins and responders can create resources
        if (!Auth::user()->isAdmin() && !Auth::user()->isResponder()) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:vehicle,equipment,personnel,facility',
            'identifier' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:available,in_use,maintenance,unavailable',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'metadata' => 'nullable|array',
        ]);

        // Auto-assign responder_id if user is a responder (not admin)
        if (Auth::user()->isResponder() && !Auth::user()->isAdmin()) {
            $validated['responder_id'] = Auth::id();
        }

        $resource = Resource::create($validated);

        // Load relationships
        $resource->load(['assignedUser', 'currentReport', 'responder']);

        return response()->json([
            'status' => 'success',
            'message' => 'Resource created successfully',
            'resource' => $resource,
        ]);
    }

    public function update(Request $request, $id)
    {
        $resource = Resource::findOrFail($id);

        // Admins can update any resource, responders can only update their own
        if (!Auth::user()->isAdmin()) {
            if (!Auth::user()->isResponder() || $resource->responder_id !== Auth::id()) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
            }
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'type' => 'sometimes|required|in:vehicle,equipment,personnel,facility',
            'identifier' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'status' => 'sometimes|required|in:available,in_use,maintenance,unavailable',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'metadata' => 'nullable|array',
        ]);

        $resource->update($validated);

        // Reload relationships
        $resource->load(['assignedUser', 'currentReport', 'responder']);

        return response()->json([
            'status' => 'success',
            'message' => 'Resource updated successfully',
            'resource' => $resource,
        ]);
    }

    public function assignToReport(Request $request, $resourceId)
    {
        // Only admins and responders can assign resources
        if (!Auth::user()->isAdmin() && !Auth::user()->isResponder()) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $resource = Resource::findOrFail($resourceId);
        $validated = $request->validate([
            'report_id' => 'required|exists:emergency_reports,id',
        ]);

        $report = EmergencyReport::findOrFail($validated['report_id']);

        // For responders, ensure they can only assign their own resources
        if (Auth::user()->isResponder() && !Auth::user()->isAdmin()) {
            if ($resource->responder_id !== Auth::id()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You can only assign resources that you own.',
                ], 403);
            }

            // Check if responder is assigned to this report
            $isAssignedToReport = \App\Models\EmergencyReportResponder::where('emergency_report_id', $validated['report_id'])
                ->where('responder_id', Auth::id())
                ->whereNotIn('status', ['cancelled'])
                ->exists();

            if (!$isAssignedToReport) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You can only assign resources to reports you are responding to.',
                ], 403);
            }
        }

        // Check if resource is available
        if ($resource->status !== 'available') {
            return response()->json([
                'status' => 'error',
                'message' => 'Resource is not available',
            ], 400);
        }

        $resource->update([
            'current_report_id' => $validated['report_id'],
            'status' => 'in_use',
            'assigned_to' => Auth::id(),
        ]);

        // Update or create usage log entry
        ResourceUsageLog::updateOrCreate(
            [
                'emergency_report_id' => $validated['report_id'],
                'resource_id' => $resource->id,
            ],
            [
                'responder_id' => Auth::id(),
                'assigned_by' => Auth::id(),
                'assigned_at' => now(),
                'released_at' => null, // Reset released_at if reassigning
                'notes' => 'Resource assigned to report',
            ]
        );

        // Reload relationships
        $resource->load(['assignedUser', 'currentReport', 'responder']);

        return response()->json([
            'status' => 'success',
            'message' => 'Resource assigned to report',
            'resource' => $resource,
        ]);
    }

    public function release($resourceId)
    {
        // Only admins and responders can release resources
        if (!Auth::user()->isAdmin() && !Auth::user()->isResponder()) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $resource = Resource::findOrFail($resourceId);

        // Ensure only the responder who assigned the resource can release it (unless admin)
        if (!Auth::user()->isAdmin() && $resource->assigned_to !== Auth::id()) {
            return response()->json([
                'status' => 'error',
                'message' => 'You can only release resources that you assigned.',
            ], 403);
        }

        // Store report ID before updating
        $reportId = $resource->current_report_id;

        $resource->update([
            'current_report_id' => null,
            'status' => 'available',
            'assigned_to' => null,
        ]);

        // Update usage log if exists (mark as released)
        if ($reportId) {
            ResourceUsageLog::where('resource_id', $resourceId)
                ->where('emergency_report_id', $reportId)
                ->whereNull('released_at')
                ->update([
                    'released_at' => now(),
                    'notes' => 'Resource released from report',
                ]);
        }

        // Reload relationships
        $resource->load(['assignedUser', 'currentReport', 'responder']);

        return response()->json([
            'status' => 'success',
            'message' => 'Resource released',
            'resource' => $resource,
        ]);
    }

    public function getAvailable(Request $request)
    {
        // Only admins and responders can view resources
        if (!Auth::user()->isAdmin() && !Auth::user()->isResponder()) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $reportId = $request->get('report_id');
        $getReports = $request->get('get_reports');

        // Get reports that the responder is assigned to (for dropdown)
        $assignedReports = [];
        if ($getReports && Auth::user()->isResponder() && !Auth::user()->isAdmin()) {
            $assignedReports = \App\Models\EmergencyReportResponder::where('responder_id', Auth::id())
                ->whereNotIn('status', ['cancelled', 'completed'])
                ->with('emergencyReport:id,type,address,created_at')
                ->get()
                ->map(function($assignment) {
                    return [
                        'id' => $assignment->emergency_report_id,
                        'type' => $assignment->emergencyReport->type ?? 'Unknown',
                        'address' => $assignment->emergencyReport->address ?? 'Unknown',
                        'created_at' => $assignment->emergencyReport->created_at ?? now(),
                        'status' => $assignment->status,
                    ];
                });
        }

        // For responders, only show their own resources. Admins see all resources.
        $availableQuery = Resource::where('status', 'available');
        if (Auth::user()->isResponder() && !Auth::user()->isAdmin()) {
            $availableQuery->where('responder_id', Auth::id());
        }
        $availableResources = $availableQuery->select('id', 'name', 'type', 'identifier', 'description')
            ->get();

        // Get resources assigned to this responder for this report
        $assignedQuery = Resource::where('assigned_to', Auth::id())
            ->where('current_report_id', $reportId)
            ->where('status', 'in_use');
        if (Auth::user()->isResponder() && !Auth::user()->isAdmin()) {
            $assignedQuery->where('responder_id', Auth::id());
        }
        $assignedResources = $assignedQuery->select('id', 'name', 'type', 'identifier', 'description')
            ->get();

        $response = [
            'status' => 'success',
            'availableResources' => $availableResources,
            'assignedResources' => $assignedResources,
        ];

        // Include assigned reports if requested
        if ($getReports) {
            $response['assignedReports'] = $assignedReports;
        }

        return response()->json($response);
    }

    public function destroy($id)
    {
        $resource = Resource::findOrFail($id);

        // Admins can delete any resource, responders can only delete their own
        if (!Auth::user()->isAdmin()) {
            if (!Auth::user()->isResponder() || $resource->responder_id !== Auth::id()) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
            }
        }

        // Check if resource is in use
        if ($resource->status === 'in_use') {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot delete resource that is currently in use',
            ], 400);
        }

        $resource->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Resource deleted successfully',
        ]);
    }
}

