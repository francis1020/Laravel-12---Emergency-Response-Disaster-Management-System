@extends('layouts.app')

@section('title', 'Reports Management')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-0"><i class="bi bi-file-earmark-text me-2"></i>Reports Management</h2>
            <p class="text-muted">View and manage all emergency reports</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.reports') }}" class="row g-3">
                <div class="col-md-4">
                    <label for="admin_status" class="form-label">Status</label>
                    <select name="status" id="admin_status" class="form-select form-select-sm">
                        <option value="">All Statuses</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="acknowledged" {{ request('status') === 'acknowledged' ? 'selected' : '' }}>Acknowledged</option>
                        <option value="dispatched" {{ request('status') === 'dispatched' ? 'selected' : '' }}>Dispatched</option>
                        <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                        <option value="resolved" {{ request('status') === 'resolved' ? 'selected' : '' }}>Resolved</option>
                        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="admin_type" class="form-label">Type</label>
                    <select name="type" id="admin_type" class="form-select form-select-sm">
                        <option value="">All Types</option>
                        @foreach(\App\Models\EmergencyType::all() as $type)
                            <option value="{{ $type->code }}" {{ request('type') === $type->code ? 'selected' : '' }}>
                                {{ $type->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="admin_search" class="form-label">Search</label>
                    <input type="text" name="search" id="admin_search" class="form-control form-control-sm" placeholder="Search..." value="{{ request('search') }}">
                </div>
                <div class="col-md-12">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-search me-2"></i>Filter
                    </button>
                    <a href="{{ route('admin.reports') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-x-circle me-2"></i>Clear
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Reports Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            @if($reports->count() > 0)
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
                            @foreach($reports as $report)
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
                                        <i class="bi bi-eye"></i> <span class="d-none d-md-inline">View</span>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div>
                    {{ $reports->appends(request()->query())->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="bi bi-inbox fs-1 text-muted"></i>
                    <p class="text-muted mt-3">No reports found</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

