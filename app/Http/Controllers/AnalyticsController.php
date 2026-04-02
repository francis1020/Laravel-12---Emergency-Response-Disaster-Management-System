<?php

namespace App\Http\Controllers;

use App\Models\EmergencyReport;
use App\Models\EmergencyReportResponder;
use App\Models\Resource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    /**
     * Get comprehensive analytics dashboard data.
     */
    public function index(Request $request)
    {
        $period = $request->get('period', '30'); // days
        $startDate = Carbon::now()->subDays($period);
        $endDate = Carbon::now();

        $analytics = [
            'overview' => $this->getOverviewStats($startDate, $endDate),
            'by_type' => $this->getReportsByType($startDate, $endDate),
            'by_severity' => $this->getReportsBySeverity($startDate, $endDate),
            'by_status' => $this->getReportsByStatus($startDate, $endDate),
            'response_times' => $this->getResponseTimeStats($startDate, $endDate),
            'responder_performance' => $this->getResponderPerformance($startDate, $endDate),
            'resource_utilization' => $this->getResourceUtilization($startDate, $endDate),
            'trends' => $this->getTrends($startDate, $endDate),
            'geographic_distribution' => $this->getGeographicDistribution($startDate, $endDate),
        ];

        // Return JSON for API requests, view for web requests
        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'analytics' => $analytics,
                'period' => $period,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ]);
        }

        return view('analytics.index', compact('analytics', 'period', 'startDate', 'endDate'));
    }

    /**
     * Get overview statistics.
     */
    private function getOverviewStats($startDate, $endDate)
    {
        $reports = EmergencyReport::whereBetween('created_at', [$startDate, $endDate])->get();

        return [
            'total_reports' => $reports->count(),
            'pending' => $reports->filter(function($r) { return $r->status === 'pending'; })->count(),
            'in_progress' => $reports->filter(function($r) { return $r->status === 'in_progress'; })->count(),
            'resolved' => $reports->filter(function($r) { return $r->status === 'resolved'; })->count(),
            'cancelled' => $reports->filter(function($r) { return $r->status === 'cancelled'; })->count(),
            'average_priority_score' => $reports->avg('priority_score') ?? 0,
            'duplicate_reports' => $reports->where('is_duplicate', true)->count(),
        ];
    }

    /**
     * Get reports grouped by type.
     */
    private function getReportsByType($startDate, $endDate)
    {
        return EmergencyReport::whereBetween('created_at', [$startDate, $endDate])
            ->select('type', DB::raw('count(*) as count'))
            ->groupBy('type')
            ->orderBy('count', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'type' => $item->type,
                    'count' => $item->count,
                ];
            });
    }

    /**
     * Get reports grouped by severity.
     */
    private function getReportsBySeverity($startDate, $endDate)
    {
        return EmergencyReport::whereBetween('created_at', [$startDate, $endDate])
            ->select('severity_level', DB::raw('count(*) as count'))
            ->groupBy('severity_level')
            ->get()
            ->map(function ($item) {
                return [
                    'severity' => $item->severity_level,
                    'count' => $item->count,
                ];
            });
    }

    /**
     * Get reports grouped by status.
     */
    private function getReportsByStatus($startDate, $endDate)
    {
        $reports = EmergencyReport::whereBetween('created_at', [$startDate, $endDate])->get();
        
        $statusCounts = [
            'pending' => 0,
            'acknowledged' => 0,
            'dispatched' => 0,
            'in_progress' => 0,
            'resolved' => 0,
            'cancelled' => 0,
        ];
        
        foreach ($reports as $report) {
            $status = $report->status; // Use accessor
            if (isset($statusCounts[$status])) {
                $statusCounts[$status]++;
            }
        }
        
        return collect($statusCounts)->map(function ($count, $status) {
            return [
                'status' => $status,
                'count' => $count,
            ];
        })->values();
    }

    /**
     * Get response time statistics.
     */
    private function getResponseTimeStats($startDate, $endDate)
    {
        $responderAssignments = EmergencyReportResponder::whereBetween('created_at', [$startDate, $endDate])
            ->where('role', 'primary')
            ->where('status', 'completed')
            ->whereNotNull('assigned_at')
            ->whereNotNull('completed_at')
            ->with('emergencyReport')
            ->get();

        $responseTimes = $responderAssignments->map(function ($assignment) {
            if ($assignment->assigned_at && $assignment->completed_at) {
                return $assignment->assigned_at->diffInMinutes($assignment->completed_at);
            }
            return null;
        })->filter();

        return [
            'average' => $responseTimes->avg() ?? 0,
            'min' => $responseTimes->min() ?? 0,
            'max' => $responseTimes->max() ?? 0,
            'median' => $responseTimes->median() ?? 0,
        ];
    }

    /**
     * Get responder performance metrics.
     */
    private function getResponderPerformance($startDate, $endDate)
    {
        return EmergencyReportResponder::whereBetween('created_at', [$startDate, $endDate])
            ->select('responder_id', DB::raw('count(*) as assignments'))
            ->groupBy('responder_id')
            ->with('responder:id,name')
            ->orderBy('assignments', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'responder_id' => $item->responder_id,
                    'responder_name' => $item->responder->name ?? 'Unknown',
                    'assignments' => $item->assignments,
                ];
            });
    }

    /**
     * Get resource utilization statistics.
     */
    private function getResourceUtilization($startDate, $endDate)
    {
        $resources = Resource::all();

        return [
            'total' => $resources->count(),
            'available' => $resources->where('status', 'available')->count(),
            'in_use' => $resources->where('status', 'in_use')->count(),
            'maintenance' => $resources->where('status', 'maintenance')->count(),
            'unavailable' => $resources->where('status', 'unavailable')->count(),
            'utilization_rate' => $resources->count() > 0 
                ? ($resources->where('status', 'in_use')->count() / $resources->count()) * 100 
                : 0,
        ];
    }

    /**
     * Get trends over time.
     */
    private function getTrends($startDate, $endDate)
    {
        $reports = EmergencyReport::whereBetween('created_at', [$startDate, $endDate])
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('count(*) as count')
            )
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        return $reports->map(function ($item) {
            return [
                'date' => $item->date,
                'count' => $item->count,
            ];
        });
    }

    /**
     * Get geographic distribution of reports.
     */
    private function getGeographicDistribution($startDate, $endDate)
    {
        return EmergencyReport::whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->select(
                DB::raw('ROUND(latitude, 2) as lat'),
                DB::raw('ROUND(longitude, 2) as lng'),
                DB::raw('count(*) as count')
            )
            ->groupBy('lat', 'lng')
            ->get()
            ->map(function ($item) {
                return [
                    'latitude' => (float) $item->lat,
                    'longitude' => (float) $item->lng,
                    'count' => $item->count,
                ];
            });
    }
}

