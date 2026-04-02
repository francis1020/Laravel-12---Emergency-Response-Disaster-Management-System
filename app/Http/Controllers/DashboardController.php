<?php

namespace App\Http\Controllers;

use App\Models\EmergencyReport;
use App\Models\User;
use App\Models\EmergencyType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        if (!$user) {
            return redirect()->route('login');
        }

        // Get statistics based on user type
        if ($user->isAdmin()) {
            return $this->adminDashboard();
        } elseif ($user->isResponder()) {
            return $this->responderDashboard($user);
        } else {
            return $this->userDashboard($user);
        }
    }

    private function adminDashboard()
    {
        $stats = [
            'total_reports' => EmergencyReport::count(),
            'pending_reports' => EmergencyReport::whereDoesntHave('responders')->count(),
            'active_reports' => EmergencyReport::whereHas('responders', function($q) {
                $q->where('role', 'primary')
                  ->whereIn('status', ['assigned', 'en_route', 'on_scene']);
            })->count(),
            'resolved_reports' => EmergencyReport::whereHas('responders', function($q) {
                $q->where('role', 'primary')
                  ->where('status', 'completed');
            })->count(),
            'cancelled_reports' => EmergencyReport::whereHas('responders', function($q) {
                $q->where('role', 'primary')
                  ->where('status', 'cancelled');
            })->count(),
            'total_responders' => User::where('type', 1)->count(),
            'total_users' => User::where('type', 0)->count(),
        ];

        // Reports by type
        $reportsByType = EmergencyReport::select('type', DB::raw('count(*) as count'))
            ->groupBy('type')
            ->get();

        // Reports by severity
        $reportsBySeverity = EmergencyReport::select('severity_level', DB::raw('count(*) as count'))
            ->groupBy('severity_level')
            ->get();

        // Recent reports (last 7 days)
        $recentReports = EmergencyReport::where('created_at', '>=', Carbon::now()->subDays(7))
            ->orderBy('created_at', 'desc')
            ->with(['user', 'emergencyType', 'responders'])
            ->limit(10)
            ->get();

        // Average response times
        $avgResponseTime = $this->calculateAverageResponseTime();
        
        // Reports by status (based on primary responder status)
        $reportsByStatus = EmergencyReport::with('responders')
            ->get()
            ->groupBy(function($report) {
                return $report->status; // Use accessor
            })
            ->map(function($reports, $status) {
                return [
                    'status' => $status,
                    'count' => $reports->count()
                ];
            })
            ->values();

        // Daily reports (last 30 days)
        $dailyReports = EmergencyReport::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('count(*) as count')
            )
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        return view('dashboard.admin', compact(
            'stats',
            'reportsByType',
            'reportsBySeverity',
            'recentReports',
            'avgResponseTime',
            'reportsByStatus',
            'dailyReports'
        ));
    }

    private function responderDashboard($user)
    {
        // Load responder detail with emergency types
        $user->load('responderDetail');
        
        // Calculate total available based on responder's emergency types
        // Reports with no assigned responders
        $availableQuery = EmergencyReport::whereDoesntHave('responders');
        
        $responderDetail = $user->responderDetail;
        if ($responderDetail && $responderDetail->emergency_types && count($responderDetail->emergency_types) > 0) {
            $availableQuery->whereIn('type', $responderDetail->emergency_types);
        }
        
        // Get reports where user is assigned as responder
        $myReportIds = \App\Models\EmergencyReportResponder::where('responder_id', $user->id)
            ->pluck('emergency_report_id');
        
        // Get user's responder assignments to check their statuses
        $myAssignments = \App\Models\EmergencyReportResponder::where('responder_id', $user->id)
            ->get()
            ->groupBy('emergency_report_id');
        
        $myPending = 0;
        $myActive = 0;
        $myResolved = 0;
        
        foreach ($myAssignments as $reportId => $assignments) {
            $myAssignment = $assignments->first();
            if ($myAssignment->status === 'assigned') {
                $myPending++;
            } elseif (in_array($myAssignment->status, ['en_route', 'on_scene'])) {
                $myActive++;
            } elseif ($myAssignment->status === 'completed') {
                $myResolved++;
            }
        }
        
        $stats = [
            'my_pending' => $myPending,
            'my_active' => $myActive,
            'my_resolved' => $myResolved,
            'total_available' => $availableQuery->count(),
        ];

        // My recent reports
        $myReports = EmergencyReport::whereIn('id', $myReportIds)
            ->orderBy('created_at', 'desc')
            ->with(['user', 'emergencyType', 'responders'])
            ->limit(10)
            ->get();

        // My response time stats
        $myAvgResponseTime = $this->calculateResponderResponseTime($user->id);

        // Available reports - filter by responder's emergency types
        $availableReportsQuery = EmergencyReport::whereDoesntHave('responders');
        
        // Filter by responder's selected emergency types
        $responderDetail = $user->responderDetail;
        if ($responderDetail && $responderDetail->emergency_types && count($responderDetail->emergency_types) > 0) {
            $availableReportsQuery->whereIn('type', $responderDetail->emergency_types);
        }
        
        $availableReports = $availableReportsQuery
            ->orderBy('severity_level', 'desc')
            ->orderBy('created_at', 'asc')
            ->with(['user', 'emergencyType'])
            ->limit(5)
            ->get();

        return view('dashboard.responder', compact(
            'stats',
            'myReports',
            'myAvgResponseTime',
            'availableReports'
        ));
    }

    private function userDashboard($user)
    {
        $stats = [
            'my_reports' => EmergencyReport::where('user_id', $user->id)->count(),
            'pending' => EmergencyReport::where('user_id', $user->id)
                ->whereDoesntHave('responders')->count(),
            'active' => EmergencyReport::where('user_id', $user->id)
                ->whereHas('responders', function($q) {
                    $q->where('role', 'primary')
                      ->whereIn('status', ['assigned', 'en_route', 'on_scene']);
                })->count(),
            'resolved' => EmergencyReport::where('user_id', $user->id)
                ->whereHas('responders', function($q) {
                    $q->where('role', 'primary')
                      ->where('status', 'completed');
                })->count(),
        ];

        // My reports
        $myReports = EmergencyReport::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->with(['emergencyType', 'user', 'responders'])
            ->limit(10)
            ->get();

        return view('dashboard.user', compact('stats', 'myReports'));
    }

    private function calculateAverageResponseTime()
    {
        $responderAssignments = \App\Models\EmergencyReportResponder::where('role', 'primary')
            ->where('status', 'completed')
            ->whereNotNull('assigned_at')
            ->whereNotNull('completed_at')
            ->with('emergencyReport')
            ->get();

        if ($responderAssignments->isEmpty()) {
            return null;
        }

        $totalSeconds = 0;
        foreach ($responderAssignments as $assignment) {
            $totalSeconds += $assignment->assigned_at->diffInSeconds($assignment->completed_at);
        }

        $avgSeconds = $totalSeconds / $responderAssignments->count();
        
        return [
            'hours' => floor($avgSeconds / 3600),
            'minutes' => floor(($avgSeconds % 3600) / 60),
            'seconds' => $avgSeconds % 60,
            'formatted' => $this->formatDuration($avgSeconds)
        ];
    }

    private function calculateResponderResponseTime($responderId)
    {
        $responderAssignments = \App\Models\EmergencyReportResponder::where('responder_id', $responderId)
            ->where('role', 'primary')
            ->where('status', 'completed')
            ->whereNotNull('assigned_at')
            ->whereNotNull('completed_at')
            ->get();

        if ($responderAssignments->isEmpty()) {
            return null;
        }

        $totalSeconds = 0;
        foreach ($responderAssignments as $assignment) {
            $totalSeconds += $assignment->assigned_at->diffInSeconds($assignment->completed_at);
        }

        $avgSeconds = $totalSeconds / $responderAssignments->count();
        
        return [
            'hours' => floor($avgSeconds / 3600),
            'minutes' => floor(($avgSeconds % 3600) / 60),
            'seconds' => $avgSeconds % 60,
            'formatted' => $this->formatDuration($avgSeconds),
            'total_resolved' => $responderAssignments->count()
        ];
    }

    private function formatDuration($seconds)
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;

        if ($hours > 0) {
            return sprintf('%dh %dm %ds', $hours, $minutes, $secs);
        } elseif ($minutes > 0) {
            return sprintf('%dm %ds', $minutes, $secs);
        } else {
            return sprintf('%ds', $secs);
        }
    }
}

