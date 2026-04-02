@extends('layouts.app')

@section('title', 'Responder Dashboard')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-0"><i class="bi bi-speedometer2 me-2"></i>Responder Dashboard</h2>
            <p class="text-muted">Your response statistics and available reports</p>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">My Pending</h6>
                            <h3 class="mb-0 text-warning">{{ $stats['my_pending'] }}</h3>
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
                            <h6 class="text-muted mb-1">My Active</h6>
                            <h3 class="mb-0 text-info">{{ $stats['my_active'] }}</h3>
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
                            <h6 class="text-muted mb-1">My Resolved</h6>
                            <h3 class="mb-0 text-success">{{ $stats['my_resolved'] }}</h3>
                        </div>
                        <div class="bg-success bg-opacity-10 p-3 rounded">
                            <i class="bi bi-check-circle fs-4 text-success"></i>
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
                            <h6 class="text-muted mb-1">Available</h6>
                            <h3 class="mb-0 text-primary">{{ $stats['total_available'] }}</h3>
                        </div>
                        <div class="bg-primary bg-opacity-10 p-3 rounded">
                            <i class="bi bi-file-earmark-plus fs-4 text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Response Time & Available Reports -->
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-stopwatch me-2"></i>My Response Time</h5>
                </div>
                <div class="card-body">
                    @if($myAvgResponseTime)
                        <h3 class="text-primary">{{ $myAvgResponseTime['formatted'] }}</h3>
                        <p class="text-muted mb-0">Average response time for {{ $myAvgResponseTime['total_resolved'] }} resolved reports</p>
                    @else
                        <p class="text-muted mb-0">No resolved reports yet</p>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-bell me-2"></i>Available Reports</h5>
                    <a href="{{ route('reports.map') }}" class="btn btn-sm btn-outline-primary">View Map</a>
                </div>
                <div class="card-body">
                    @if($availableReports->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($availableReports as $report)
                            <div class="list-group-item px-0">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1">#{{ $report->id }} - {{ ucfirst(str_replace('_', ' ', $report->type)) }}</h6>
                                        <p class="mb-1 text-muted small">{{ Str::limit($report->description ?? 'No description', 50) }}</p>
                                        <small class="text-muted">{{ $report->address ?? 'No address' }}</small>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-{{ $report->severity_level === 'critical' ? 'danger' : ($report->severity_level === 'high' ? 'warning' : ($report->severity_level === 'moderate' ? 'info' : 'success')) }} mb-2">
                                            {{ ucfirst($report->severity_level) }}
                                        </span>
                                        <br>
                                        <a href="{{ route('response.show', $report->id) }}" class="btn btn-sm btn-primary mt-1">
                                            <i class="bi bi-eye"></i> View
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted mb-0">No available reports at the moment</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- My Recent Reports -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i>My Recent Responses</h5>
                    <a href="{{ route('reports.history') }}" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body">
                    @if($myReports->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Type</th>
                                        <th>Severity</th>
                                        <th>Status (Responders)</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($myReports as $report)
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
                                        <td>{{ $report->created_at->format('M d, Y g:i A') }}</td>
                                        <td>
                                            @php
                                                // Check if report is completed (any responder has status 'completed')
                                                $isCompleted = $report->responders && $report->responders->where('status', 'completed')->count() > 0;
                                                // Check if primary responder status is cancelled
                                                $primaryResponder = $report->responders ? $report->responders->where('role', 'primary')->first() : null;
                                                $isPrimaryCancelled = $primaryResponder && $primaryResponder->status === 'cancelled';
                                            @endphp
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('reports.show', $report->id) }}" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-eye"></i> View
                                                </a>
                                                @if(!$isCompleted && !$isPrimaryCancelled)
                                                    <a href="{{ route('response.show', $report->id) }}" class="btn btn-sm btn-primary">
                                                        <i class="bi bi-arrow-right-circle"></i> Response
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted mb-0">No reports assigned to you yet</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

