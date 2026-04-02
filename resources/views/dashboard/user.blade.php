@extends('layouts.app')

@section('title', 'My Dashboard')

@section('content')
<style>
    @media (max-width: 767.98px) {
    
    .row.mb-3 h2 {
        font-size: 1.25rem;
    }
    
    .row.mb-3 p {
        font-size: 0.875rem;
    }
}
</style>
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-0"><i class="bi bi-speedometer2 me-2"></i>My Dashboard</h2>
            <p class="text-muted">Your emergency reports overview</p>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">My Reports</h6>
                            <h3 class="mb-0">{{ $stats['my_reports'] }}</h3>
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
                            <h3 class="mb-0 text-warning">{{ $stats['pending'] }}</h3>
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
                            <h3 class="mb-0 text-info">{{ $stats['active'] }}</h3>
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
                            <h3 class="mb-0 text-success">{{ $stats['resolved'] }}</h3>
                        </div>
                        <div class="bg-success bg-opacity-10 p-3 rounded">
                            <i class="bi bi-check-circle fs-4 text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="mb-3"><i class="bi bi-lightning-charge me-2"></i>Quick Actions</h5>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="{{ route('reports.map') }}?new_report=1" class="btn btn-primary btn-sm">
                            <i class="bi bi-map me-2"></i>Report Emergency
                        </a>
                        <a href="{{ route('reports.history') }}" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-clock-history me-2"></i>View Report History
                        </a>
                        <a href="{{ route('reports.export.csv') }}" class="btn btn-outline-success btn-sm">
                            <i class="bi bi-download me-2"></i>Export Reports (CSV)
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- My Reports -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i>My Recent Reports</h5>
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
                                                <span class="badge bg-danger mb-1 d-inline-block">Cancelled by you</span>
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
                        <div class="text-center py-5">
                            <i class="bi bi-inbox fs-1 text-muted"></i>
                            <p class="text-muted mt-3">You haven't submitted any reports yet</p>
                            <a href="{{ route('reports.map') }}?new_report=1" class="btn btn-primary btn-sm">
                                <i class="bi bi-plus-circle me-2"></i>Report Emergency
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

