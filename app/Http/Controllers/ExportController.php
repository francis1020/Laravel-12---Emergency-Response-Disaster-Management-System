<?php

namespace App\Http\Controllers;

use App\Models\EmergencyReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;

class ExportController extends Controller
{
    public function exportCsv(Request $request)
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

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $reports = $query->with(['user', 'emergencyType', 'primaryResponder.responder'])->get();

        $filename = 'emergency_reports_' . date('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($reports) {
            $file = fopen('php://output', 'w');
            
            // Headers
            fputcsv($file, [
                'ID',
                'Type',
                'Severity',
                'Status',
                'Description',
                'Contact Name',
                'Contact Number',
                'Email',
                'Address',
                'Latitude',
                'Longitude',
                'Reported At',
                'Responder',
                'Response Notes'
            ]);

            // Data
            foreach ($reports as $report) {
                fputcsv($file, [
                    $report->id,
                    $report->type,
                    $report->severity_level,
                    $report->status, // Use accessor
                    $report->description,
                    $report->contact_name,
                    $report->contact_number,
                    $report->email,
                    $report->address,
                    $report->latitude,
                    $report->longitude,
                    $report->reported_at,
                    $report->primaryResponder ? $report->primaryResponder->responder->name ?? 'N/A' : 'N/A',
                    $report->primaryResponder ? $report->primaryResponder->response_notes ?? 'N/A' : 'N/A'
                ]);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    public function exportPdf(Request $request)
    {
        // Note: This is a basic implementation. For full PDF generation,
        // you would typically use a package like dompdf or barryvdh/laravel-dompdf
        
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

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $reports = $query->with(['user', 'emergencyType', 'primaryResponder.responder'])->get();

        // Generate HTML content
        $html = view('exports.reports-pdf', compact('reports', 'user'))->render();

        // For now, return HTML. To generate actual PDF, install dompdf:
        // composer require barryvdh/laravel-dompdf
        // Then use: return PDF::loadHTML($html)->download('reports.pdf');
        
        return response($html)
            ->header('Content-Type', 'text/html')
            ->header('Content-Disposition', 'inline; filename="reports.html"');
    }
}

