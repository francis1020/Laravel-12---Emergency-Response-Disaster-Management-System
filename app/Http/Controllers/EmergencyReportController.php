<?php

namespace App\Http\Controllers;

use App\Models\EmergencyReport;
use App\Models\EmergencyReportResponder;
use App\Models\EmergencyResponderLocationLog;
use App\Models\EmergencyType;
use App\Models\User;
use App\Events\EmergencyLocationSend;
use App\Events\ResponderNotificationSent;
use App\Notifications\EmergencyReportStatusChanged;
use App\Notifications\ResponderStatusChanged;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class EmergencyReportController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // If report_id is provided, only show that specific report
        if ($request->has('report_id') && $request->report_id) {
            $reports = EmergencyReport::where('id', $request->report_id)
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->get();
        } else {
            // Get all reports with coordinates
            // Exclude cancelled reports (is_cancelled = 1) and resolved reports (primary responder completed)
            $reportsQuery = EmergencyReport::whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->where('is_cancelled', false) // Exclude cancelled reports
                ->where(function($query) {
                    // Include reports with no responders (pending)
                    $query->whereDoesntHave('responders')
                    // OR include reports where primary responder exists but is NOT completed
                    ->orWhereHas('responders', function($q) {
                        $q->where('role', 'primary')
                          ->where('status', '!=', 'completed');
                    });
                });
            
            // Admins and super admins see all reports
            // Responders see only reports matching their emergency types
            if ($user && $user->isResponder() && !$user->isAdmin()) {
                $user->load('responderDetail');
                $responderDetail = $user->responderDetail;
                if ($responderDetail && $responderDetail->emergency_types && count($responderDetail->emergency_types) > 0) {
                    $reportsQuery->whereIn('type', $responderDetail->emergency_types);
                }
            }
            // Admins and super admins see all reports (no filtering)
            
            $reports = $reportsQuery->with('responders')->get();
        }

        // Get all assigned responders from emergency_report_responders table
        // Only show responders with status 'en_route' or 'on_scene'
        $assignedRespondersQuery = \App\Models\EmergencyReportResponder::query()
            ->join('emergency_reports as r', 'r.id', '=', 'emergency_report_responders.emergency_report_id')
            ->join('users as u', 'u.id', '=', 'emergency_report_responders.responder_id')
            ->whereIn('emergency_report_responders.status', ['en_route', 'on_scene'])
            ->whereHas('emergencyReport', function($q) {
                // Only include reports that are not cancelled and primary responder is not completed
                $q->where('is_cancelled', false)
                  ->where(function($subQ) {
                      $subQ->whereDoesntHave('responders')
                           ->orWhereHas('responders', function($responderQ) {
                               $responderQ->where('role', 'primary')
                                          ->where('status', '!=', 'completed');
                           });
                  });
            });
        
        if ($request->has('report_id') && $request->report_id) {
            $assignedRespondersQuery->where('emergency_report_responders.emergency_report_id', $request->report_id);
        }
        
        $assignedResponders = $assignedRespondersQuery
            ->select(
                'emergency_report_responders.emergency_report_id as report_id',
                'emergency_report_responders.responder_id',
                'emergency_report_responders.role as responder_role',
                'emergency_report_responders.status as responder_status',
                'u.name as responder_name'
            )
            ->get();
        
        // Get latest location for each responder from location logs
        $locationLogsQuery = EmergencyResponderLocationLog::query()
            ->whereIn('report_id', $assignedResponders->pluck('report_id')->unique())
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->orderBy('created_at', 'desc');
        
        $locationLogs = $locationLogsQuery
            ->get()
            ->groupBy(function ($item) {
                return $item->report_id . '-' . $item->responder_id;
            })
            ->map(function ($group) {
                return $group->first(); // Get latest location
            });
        
        // Merge assigned responders with their latest location data
        $responders = $assignedResponders->map(function ($assigned) use ($locationLogs) {
            $locationKey = $assigned->report_id . '-' . $assigned->responder_id;
            $location = $locationLogs->get($locationKey);
            
            // If no location log exists, use report location as fallback
            if (!$location) {
                $report = \App\Models\EmergencyReport::find($assigned->report_id);
                return (object)[
                    'report_id' => $assigned->report_id,
                    'responder_id' => $assigned->responder_id,
                    'responder_name' => $assigned->responder_name,
                    'responder_role' => $assigned->responder_role,
                    'responder_status' => $assigned->responder_status,
                    'latitude' => $report->latitude ?? null,
                    'longitude' => $report->longitude ?? null,
                    'message' => 'Assigned - No location update yet',
                    'summary' => null,
                    'severity_level' => $report->severity_level ?? null,
                ];
            }
            
            // Merge location data with assigned responder data
            return (object)[
                'report_id' => $location->report_id,
                'responder_id' => $location->responder_id,
                'responder_name' => $location->responder_name ?? $assigned->responder_name,
                'responder_role' => $assigned->responder_role,
                'responder_status' => $assigned->responder_status,
                'latitude' => $location->latitude,
                'longitude' => $location->longitude,
                'message' => $location->message,
                'summary' => $location->summary,
                'severity_level' => $location->severity_level,
            ];
        })
        ->filter(function ($responder) {
            // Only include responders with valid coordinates
            return $responder->latitude && $responder->longitude;
        })
        ->groupBy('report_id')
        ->map(function ($group) {
            return $group->values()->toArray();
        })
        ->toArray();
      
        $emailVerifiedAt = $user ? $user->email_verified_at : null;
        
        // Get all active emergency types for the form dropdown
        $emergencyTypes = EmergencyType::where('status', true)->orderBy('name')->get();

        return view('report.map', compact('reports', 'responders', 'emailVerifiedAt', 'user', 'emergencyTypes')); 
    }

    public function create()
    {
        // return view('report.create');
    }

    public function store(Request $request)
    {  
        $validated = $request->validate([
            'type' => [
                'required',
                'string',
                Rule::in(['fire', 'road_accident', 'police_assistance', 'medical_emergency', 'rescue', 'flood', 'earthquake', 'others']),
            ],
            'severity_level' => [
                'required',
                Rule::in(['low', 'moderate', 'high', 'critical']),
            ],
            'description' => 'nullable|string|min:10|max:1000',
            'contact_name' => 'nullable|string|max:100|regex:/^[a-zA-Z\s\.\'-]+$/',
            'contact_number' => [
                'required',
                'regex:/^09\d{9}$/',
                'max:11'
            ],
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'address' => 'nullable|string|max:255',
            'user_id' => 'nullable|exists:users,id',
            'email' => 'nullable|email|max:255',
        ]);

        $validated['user_id'] = $validated['user_id'] ?? auth()->id();
        $validated['email'] = $validated['email'] ?? auth()->user()->email ?? null;

        // Auto-detect address if not provided
        if (empty($validated['address'])) {
            try {
                $lat = $validated['latitude'];
                $lng = $validated['longitude'];
                $response = file_get_contents("https://nominatim.openstreetmap.org/reverse?lat={$lat}&lon={$lng}&format=json");
                $data = json_decode($response, true);
                $validated['address'] = $data['display_name'] ?? 'Unknown Address';
            } catch (\Exception $e) {
                $validated['address'] = 'Unknown Address';
            }
        }

        // Check for duplicate reports (within 100 meters and 5 minutes)
        $duplicateReport = $this->checkForDuplicate($validated);
        
        if ($duplicateReport) {
            $validated['is_duplicate'] = true;
            $validated['original_report_id'] = $duplicateReport->id;
        }

        $report = EmergencyReport::create($validated)->fresh();
        
        // Calculate and update priority score
        $report->priority_score = $report->calculatePriorityScore();
        $report->save();

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
            $notification = new EmergencyReportStatusChanged($report, 'pending', 'new');
            $responder->notify($notification);
            
            // Broadcast real-time notification
            $notificationData = [
                'report_id' => $report->id,
                'report_type' => $report->type,
                'report_address' => $report->address,
                'report_severity' => $report->severity_level,
                'old_status' => 'pending',
                'new_status' => 'new',
                'message' => "New emergency report #{$report->id} ({$report->type}) has been created.",
                'type' => 'info',
                'title' => 'New Emergency Report',
            ];
            broadcast(new ResponderNotificationSent($notificationData, $responder->id))->toOthers();
        }

        // Auto-assign responder if available (after notifications are sent)
        // Delay auto-assign to show after "new report" notification
        $this->autoAssignResponder($report);

        // Notify all admins and super admins (type = 2 or 3)
        $admins = User::whereIn('type', [2, 3])->get();

        foreach ($admins as $admin) {
            // Send database notification
            $notification = new EmergencyReportStatusChanged($report, 'pending', 'new');
            $admin->notify($notification);
            
            // Broadcast real-time notification
            $notificationData = [
                'report_id' => $report->id,
                'report_type' => $report->type,
                'report_address' => $report->address,
                'report_severity' => $report->severity_level,
                'old_status' => 'pending',
                'new_status' => 'new',
                'message' => "New emergency report #{$report->id} ({$report->type}) has been created.",
                'type' => 'info',
                'title' => 'New Emergency Report',
            ];
            broadcast(new ResponderNotificationSent($notificationData, $admin->id))->toOthers();
        }

        $message = $duplicateReport 
            ? 'Emergency report received. This may be a duplicate of an existing report.'
            : 'Emergency report has been successfully sent.';

        return response()->json([
            'status' => 'success',
            'message' => $message,
            'report' => $report,
            'is_duplicate' => $duplicateReport ? true : false,
        ]);
    }

    /**
     * Check for duplicate reports within proximity and time window.
     */
    private function checkForDuplicate(array $validated)
    {
        $latitude = $validated['latitude'];
        $longitude = $validated['longitude'];
        $type = $validated['type'];
        $timeWindow = now()->subMinutes(5); // Check last 5 minutes

        // Find reports of the same type within 100 meters in the last 5 minutes
        $potentialDuplicates = EmergencyReport::where('type', $type)
            ->where('created_at', '>=', $timeWindow)
            ->where('is_duplicate', false)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get();

        foreach ($potentialDuplicates as $existingReport) {
            $distance = $this->calculateDistance(
                $latitude,
                $longitude,
                $existingReport->latitude,
                $existingReport->longitude
            );

            // If within 100 meters, consider it a duplicate
            if ($distance <= 0.1) { // 0.1 km = 100 meters
                return $existingReport;
            }
        }

        return null;
    }

    /**
     * Calculate distance between two coordinates using Haversine formula.
     * Returns distance in kilometers.
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // Earth's radius in kilometers

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earthRadius * $c;

        return $distance;
    }

    /**
     * Auto-assign the nearest available responder to the emergency report.
     */
    private function autoAssignResponder(EmergencyReport $report)
    {
        // Only auto-assign if report has coordinates
        if (!$report->latitude || !$report->longitude) {
            return;
        }

        // Find responders with auto_assign enabled, matching emergency type, and available status
        $eligibleResponders = User::where('type', 1) // Only responders
            ->whereHas('responderDetail', function($query) use ($report) {
                $query->where('auto_assign', true)
                      ->where('status', 'available')
                      ->whereNotNull('emergency_types')
                      ->whereJsonContains('emergency_types', $report->type)
                      ->whereNotNull('office_location_latitude')
                      ->whereNotNull('office_location_longitude');
            })
            ->with('responderDetail')
            ->get();

        if ($eligibleResponders->isEmpty()) {
            return;
        }

        // Calculate distance for each responder and find the nearest
        $nearestResponder = null;
        $shortestDistance = PHP_FLOAT_MAX;

        foreach ($eligibleResponders as $responder) {
            $distance = $this->calculateDistance(
                $report->latitude,
                $report->longitude,
                $responder->responderDetail->office_location_latitude,
                $responder->responderDetail->office_location_longitude
            );

            if ($distance < $shortestDistance) {
                $shortestDistance = $distance;
                $nearestResponder = $responder;
            }
        }

        // Assign the nearest responder if found
        if ($nearestResponder) {
            // Check if responder is already assigned to this report
            $existingAssignment = EmergencyReportResponder::where('emergency_report_id', $report->id)
                ->where('responder_id', $nearestResponder->id)
                ->first();

            if ($existingAssignment) {
                return; // Already assigned
            }

            // Check if there's already a primary responder
            // Auto-assign only assigns as primary, so skip if primary already exists
            $hasPrimary = EmergencyReportResponder::where('emergency_report_id', $report->id)
                ->where('role', 'primary')
                ->exists();

            if ($hasPrimary) {
                return; // Skip auto-assign if primary responder already exists
            }

            // Auto-assign only as primary responder
            $role = 'primary';

            $respondingUnit = $nearestResponder->responderDetail->department ?? null;

            EmergencyReportResponder::create([
                'emergency_report_id' => $report->id,
                'responder_id' => $nearestResponder->id,
                'role' => $role,
                'status' => 'assigned',
                'assigned_at' => now(),
                'responding_unit' => $respondingUnit,
            ]);

            // Update responder status to busy
            if ($nearestResponder->responderDetail) {
                $nearestResponder->responderDetail->update(['status' => 'busy']);
            }

            // Don't save database notification for auto-assign, only broadcast real-time
            // Broadcast real-time notification (modal will be shown after "new report" notification via JS delay)
            $roleLabel = $role === 'primary' ? 'Primary' : 'Secondary';
            $notificationData = [
                'report_id' => $report->id,
                'report_type' => $report->type,
                'report_address' => $report->address,
                'report_severity' => $report->severity_level,
                'assigned_by_id' => null,
                'assigned_by_name' => 'System (Auto-Assign)',
                'action' => 'auto_assigned',
                'role' => $role,
                'role_label' => $roleLabel,
                'message' => "You have been automatically assigned as {$roleLabel} Responder to Report #{$report->id} ({$report->type}) based on proximity.",
                'type' => 'info',
                'title' => 'Auto-Assigned to Report',
            ];
            broadcast(new ResponderNotificationSent($notificationData, $nearestResponder->id))->toOthers();
        }
    }
}
