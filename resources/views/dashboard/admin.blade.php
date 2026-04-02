@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-0"><i class="bi bi-speedometer2 me-2"></i>Admin Dashboard</h2>
            <p class="text-muted">System overview and statistics</p>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Total Reports</h6>
                            <h3 class="mb-0">{{ $stats['total_reports'] }}</h3>
                        </div>
                        <div class="bg-primary bg-opacity-10 p-3 rounded">
                            <i class="bi bi-file-earmark-text fs-4 text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Pending</h6>
                            <h3 class="mb-0 text-warning">{{ $stats['pending_reports'] }}</h3>
                        </div>
                        <div class="bg-warning bg-opacity-10 p-3 rounded">
                            <i class="bi bi-clock-history fs-4 text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Active</h6>
                            <h3 class="mb-0 text-info">{{ $stats['active_reports'] }}</h3>
                        </div>
                        <div class="bg-info bg-opacity-10 p-3 rounded">
                            <i class="bi bi-activity fs-4 text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Resolved</h6>
                            <h3 class="mb-0 text-success">{{ $stats['resolved_reports'] }}</h3>
                        </div>
                        <div class="bg-success bg-opacity-10 p-3 rounded">
                            <i class="bi bi-check-circle fs-4 text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Total Responders</h6>
                            <h3 class="mb-0">{{ $stats['total_responders'] }}</h3>
                        </div>
                        <div class="bg-danger bg-opacity-10 p-3 rounded">
                            <i class="bi bi-people fs-4 text-danger"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Total Users</h6>
                            <h3 class="mb-0">{{ $stats['total_users'] }}</h3>
                        </div>
                        <div class="bg-secondary bg-opacity-10 p-3 rounded">
                            <i class="bi bi-person fs-4 text-secondary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Cancelled</h6>
                            <h3 class="mb-0 text-danger">{{ $stats['cancelled_reports'] }}</h3>
                        </div>
                        <div class="bg-danger bg-opacity-10 p-3 rounded">
                            <i class="bi bi-x-circle fs-4 text-danger"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-pie-chart me-2"></i>Reports by Type</h5>
                </div>
                <div class="card-body">
                    @if($reportsByType->count() > 0)
                        <canvas id="reportsByTypeChart" style="max-height: 300px;"></canvas>
                        <div class="table-responsive mt-3">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Type</th>
                                        <th class="text-end">Count</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($reportsByType as $type)
                                    <tr>
                                        <td>{{ ucfirst(str_replace('_', ' ', $type->type)) }}</td>
                                        <td class="text-end"><span class="badge bg-primary">{{ $type->count }}</span></td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted mb-0">No data available</p>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-bar-chart me-2"></i>Reports by Severity</h5>
                </div>
                <div class="card-body">
                    @if($reportsBySeverity->count() > 0)
                        <canvas id="reportsBySeverityChart" style="max-height: 300px;"></canvas>
                        <div class="table-responsive mt-3">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Severity</th>
                                        <th class="text-end">Count</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($reportsBySeverity as $severity)
                                    <tr>
                                        <td>
                                            <span class="badge bg-{{ $severity->severity_level === 'critical' ? 'danger' : ($severity->severity_level === 'high' ? 'warning' : ($severity->severity_level === 'moderate' ? 'info' : 'success')) }}">
                                                {{ ucfirst($severity->severity_level) }}
                                            </span>
                                        </td>
                                        <td class="text-end"><span class="badge bg-primary">{{ $severity->count }}</span></td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted mb-0">No data available</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
    
    <!-- Daily Reports Trend Chart -->
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-graph-up me-2"></i>Daily Reports Trend (Last 30 Days)</h5>
                </div>
                <div class="card-body">
                    @if($dailyReports->count() > 0)
                        <canvas id="dailyReportsChart" style="max-height: 400px;"></canvas>
                    @else
                        <p class="text-muted mb-0">No data available</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Response Time & Recent Reports -->
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-stopwatch me-2"></i>Average Response Time</h5>
                </div>
                <div class="card-body">
                    @if($avgResponseTime)
                        <h3 class="text-primary">{{ $avgResponseTime['formatted'] }}</h3>
                        <p class="text-muted mb-0">Average time from acknowledgment to resolution</p>
                    @else
                        <p class="text-muted mb-0">No resolved reports available</p>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i>Reports by Status</h5>
                </div>
                <div class="card-body">
                    @if($reportsByStatus->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Status</th>
                                        <th class="text-end">Count</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($reportsByStatus as $status)
                                    <tr>
                                        <td><span class="badge bg-secondary">{{ ucfirst(str_replace('_', ' ', $status['status'])) }}</span></td>
                                        <td class="text-end"><span class="badge bg-primary">{{ $status['count'] }}</span></td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted mb-0">No data available</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Reports -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Recent Reports (Last 7 Days)</h5>
                    <a href="{{ route('reports.history') }}" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body">
                    @if($recentReports->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Type</th>
                                        <th>Severity</th>
                                        <th>Status (Responders)</th>
                                        <th>Reported By</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentReports as $report)
                                    <tr>
                                        <td>#{{ $report->id }}</td>
                                        <td>{{ ucfirst(str_replace('_', ' ', $report->type)) }}</td>
                                        <td>
                                            <span class="badge bg-{{ $report->severity_level === 'critical' ? 'danger' : ($report->severity_level === 'high' ? 'warning' : ($report->severity_level === 'moderate' ? 'info' : 'success')) }}">
                                                {{ ucfirst($report->severity_level) }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($report->is_cancelled)
                                                <span class="badge bg-danger mb-1 d-inline-block">
                                                    @if($report->user)
                                                        Cancelled by {{ $report->user->name }}
                                                    @else
                                                        Cancelled by user
                                                    @endif
                                                </span>
                                            @elseif($report->responders && $report->responders->count() > 0)
                                                @foreach($report->responders as $responder)
                                                    @php
                                                        $statusColors = [
                                                            'assigned' => 'info',
                                                            'en_route' => 'primary',
                                                            'on_scene' => 'warning',
                                                            'completed' => 'success',
                                                            'cancelled' => 'danger'
                                                        ];
                                                        $statusColor = $statusColors[$responder->status] ?? 'secondary';
                                                        $statusLabels = [
                                                            'assigned' => 'Assigned',
                                                            'en_route' => 'En Route',
                                                            'on_scene' => 'On Scene',
                                                            'completed' => 'Completed',
                                                            'cancelled' => 'Cancelled'
                                                        ];
                                                        $statusLabel = $statusLabels[$responder->status] ?? ucfirst($responder->status);
                                                    @endphp
                                                    <span class="badge bg-{{ $statusColor }} mb-1 d-inline-block" title="{{ $responder->responder->name ?? 'N/A' }} ({{ ucfirst($responder->role) }})">
                                                        {{ $responder->responder->name ?? 'N/A' }}: {{ $statusLabel }}
                                                    </span>
                                                @endforeach
                                            @else
                                                <span class="badge bg-secondary">Pending</span>
                                            @endif
                                        </td>
                                        <td>{{ $report->user->name ?? 'Anonymous' }}</td>
                                        <td>{{ $report->created_at->format('M d, Y g:i A') }}</td>
                                        <td>
                                            <a href="{{ route('reports.show', ['report_id' => $report->id]) }}" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted mb-0">No recent reports</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- New Features Quick Access -->
    <div class="row g-3 mt-4">
        <div class="col-12">
            <h4 class="mb-3"><i class="bi bi-star me-2"></i>New Features</h4>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="bg-primary bg-opacity-10 p-3 rounded-circle d-inline-block mb-3">
                        <i class="bi bi-images fs-2 text-primary"></i>
                    </div>
                    <h5>Media Upload</h5>
                    <p class="text-muted small">Upload photos, videos, and documents to reports</p>
                    <a href="{{ route('reports.history') }}" class="btn btn-sm btn-primary">View Reports</a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="bg-success bg-opacity-10 p-3 rounded-circle d-inline-block mb-3">
                        <i class="bi bi-people fs-2 text-success"></i>
                    </div>
                    <h5>Multi-Responder</h5>
                    <p class="text-muted small">Assign multiple responders to emergency reports</p>
                    <a href="{{ route('reports.history') }}" class="btn btn-sm btn-success">Manage</a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="bg-info bg-opacity-10 p-3 rounded-circle d-inline-block mb-3">
                        <i class="bi bi-truck fs-2 text-info"></i>
                    </div>
                    <h5>Resources</h5>
                    <p class="text-muted small">Manage vehicles, equipment, and facilities</p>
                    <a href="{{ route('resources.index') }}" class="btn btn-sm btn-info">View Resources</a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="bg-warning bg-opacity-10 p-3 rounded-circle d-inline-block mb-3">
                        <i class="bi bi-graph-up fs-2 text-warning"></i>
                    </div>
                    <h5>Analytics</h5>
                    <p class="text-muted small">Comprehensive system analytics and insights</p>
                    <a href="{{ route('analytics.index') }}" class="btn btn-sm btn-warning">View Analytics</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Reports by Type - Pie Chart
    @if($reportsByType->count() > 0)
    const reportsByTypeCtx = document.getElementById('reportsByTypeChart');
    if (reportsByTypeCtx) {
        const reportsByTypeData = {
            labels: [
                @foreach($reportsByType as $type)
                '{{ ucfirst(str_replace('_', ' ', $type->type)) }}',
                @endforeach
            ],
            datasets: [{
                data: [
                    @foreach($reportsByType as $type)
                    {{ $type->count }},
                    @endforeach
                ],
                backgroundColor: [
                    'rgba(54, 162, 235, 0.8)',
                    'rgba(255, 99, 132, 0.8)',
                    'rgba(255, 206, 86, 0.8)',
                    'rgba(75, 192, 192, 0.8)',
                    'rgba(153, 102, 255, 0.8)',
                    'rgba(255, 159, 64, 0.8)',
                    'rgba(199, 199, 199, 0.8)',
                    'rgba(83, 102, 255, 0.8)',
                ],
                borderColor: [
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 99, 132, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(153, 102, 255, 1)',
                    'rgba(255, 159, 64, 1)',
                    'rgba(199, 199, 199, 1)',
                    'rgba(83, 102, 255, 1)',
                ],
                borderWidth: 2
            }]
        };
        
        new Chart(reportsByTypeCtx, {
            type: 'doughnut',
            data: reportsByTypeData,
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                label += context.parsed + ' reports';
                                return label;
                            }
                        }
                    }
                }
            }
        });
    }
    @endif

    // Reports by Severity - Bar Chart
    @if($reportsBySeverity->count() > 0)
    const reportsBySeverityCtx = document.getElementById('reportsBySeverityChart');
    if (reportsBySeverityCtx) {
        const severityColors = {
            'critical': 'rgba(220, 53, 69, 0.8)',
            'high': 'rgba(255, 193, 7, 0.8)',
            'moderate': 'rgba(13, 202, 240, 0.8)',
            'low': 'rgba(25, 135, 84, 0.8)'
        };
        
        const severityBorderColors = {
            'critical': 'rgba(220, 53, 69, 1)',
            'high': 'rgba(255, 193, 7, 1)',
            'moderate': 'rgba(13, 202, 240, 1)',
            'low': 'rgba(25, 135, 84, 1)'
        };
        
        const reportsBySeverityData = {
            labels: [
                @foreach($reportsBySeverity as $severity)
                '{{ ucfirst($severity->severity_level) }}',
                @endforeach
            ],
            datasets: [{
                label: 'Number of Reports',
                data: [
                    @foreach($reportsBySeverity as $severity)
                    {{ $severity->count }},
                    @endforeach
                ],
                backgroundColor: [
                    @foreach($reportsBySeverity as $severity)
                    severityColors['{{ $severity->severity_level }}'] || 'rgba(108, 117, 125, 0.8)',
                    @endforeach
                ],
                borderColor: [
                    @foreach($reportsBySeverity as $severity)
                    severityBorderColors['{{ $severity->severity_level }}'] || 'rgba(108, 117, 125, 1)',
                    @endforeach
                ],
                borderWidth: 2
            }]
        };
        
        new Chart(reportsBySeverityCtx, {
            type: 'bar',
            data: reportsBySeverityData,
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Reports: ' + context.parsed.y;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }
    @endif

    // Daily Reports Trend - Line Chart
    @if($dailyReports->count() > 0)
    const dailyReportsCtx = document.getElementById('dailyReportsChart');
    if (dailyReportsCtx) {
        const dailyReportsData = {
            labels: [
                @foreach($dailyReports as $daily)
                '{{ \Carbon\Carbon::parse($daily->date)->format('M d') }}',
                @endforeach
            ],
            datasets: [{
                label: 'Reports per Day',
                data: [
                    @foreach($dailyReports as $daily)
                    {{ $daily->count }},
                    @endforeach
                ],
                borderColor: 'rgba(54, 162, 235, 1)',
                backgroundColor: 'rgba(54, 162, 235, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        };
        
        new Chart(dailyReportsCtx, {
            type: 'line',
            data: dailyReportsData,
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Reports: ' + context.parsed.y;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }
    @endif
});
</script>
@endsection

