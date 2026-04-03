@extends('layouts.app')

@section('title', 'Report Details')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h2 class="mb-0">Report #{{ $report->id }}</h2>
                    <p class="text-muted mb-0">Emergency report details</p>
                </div>
                <div class="text-end">
                    <div class="btn-group-vertical" role="group">
                        @if(auth()->check() && (auth()->id() === $report->user_id || auth()->user()->isAdmin()))
                            @php
                                // Check if no responders assigned or status is pending
                                $hasResponders = $report->responders && $report->responders->count() > 0;
                                $isPending = $report->status === 'pending';
                                $isAdmin = auth()->user()->isAdmin();
                                // Admins can always edit, regular users can only edit if no responders or pending
                                $canEdit = $isAdmin || !$hasResponders || $isPending;
                            @endphp
                            @if($canEdit)
                            <button type="button" class="btn btn-warning btn-sm mb-2" data-bs-toggle="modal" data-bs-target="#editReportModal">
                                <i class="bi bi-pencil me-2"></i>Edit Report
                            </button>
                            @endif
                        @endif
                        @if(auth()->check() && (auth()->id() === $report->user_id || auth()->user()->isAdmin()))
                        @if(!$report->is_cancelled && $report->status !== 'cancelled' && $report->status !== 'resolved')
                        <button type="button" id="cancelReportBtn" class="btn btn-warning btn-sm mb-2" onclick="cancelReport({{ $report->id }})">
                            <i class="bi bi-x-circle me-2"></i>Cancel Report
                        </button>
                        @endif
                        @php
                            // Check if report has responders assigned
                            $hasResponders = $report->responders && $report->responders->count() > 0;
                            $isAdmin = auth()->user()->isAdmin();
                            // Admins can always delete, regular users can only delete if no responders
                            $canDelete = $isAdmin || !$hasResponders;
                        @endphp
                        @if($canDelete)
                        <button type="button" id="deleteReportBtn" class="btn btn-danger btn-sm mb-2" onclick="deleteReport({{ $report->id }})">
                            <i class="bi bi-trash me-2"></i>Delete Report
                        </button>
                        @endif
                        @endif
                        <a href="{{ route('reports.map') }}?report_id={{ $report->id }}" class="btn btn-primary btn-sm">
                            <i class="bi bi-map me-2"></i>View on Map
                        </a>
                        <a href="{{ route('reports.export.csv') }}?report_id={{ $report->id }}" class="btn btn-outline-success btn-sm mt-2">
                            <i class="bi bi-download me-2"></i>Export Report
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Main Details -->
        <div class="col-md-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Report Information</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <strong>Type:</strong>
                            <p>{{ ucfirst(str_replace('_', ' ', $report->type)) }}</p>
                        </div>
                        <div class="col-md-4">
                            <strong>Severity:</strong>
                            <p>
                                <span class="badge bg-{{ $report->severity_level === 'critical' ? 'danger' : ($report->severity_level === 'high' ? 'warning' : ($report->severity_level === 'moderate' ? 'info' : 'success')) }}">
                                    {{ ucfirst($report->severity_level) }}
                                </span>
                            </p>
                        </div>
                        <div class="col-md-4">
                            <strong>Status:</strong>
                            <p>
                                @if($report->is_cancelled)
                                    <span class="badge bg-danger">
                                        @if(auth()->check() && auth()->id() === $report->user_id)
                                            Cancelled by you
                                        @elseif($report->user)
                                            Cancelled by {{ $report->user->name }}
                                        @else
                                            Cancelled by user
                                        @endif
                                    </span>
                                @elseif($report->responders && $report->responders->count() > 0)
                                    @php
                                        $primaryResponder = $report->responders->where('role', 'primary')->first();
                                        $statusMap = [
                                            'assigned' => ['label' => 'Acknowledged', 'color' => 'info'],
                                            'en_route' => ['label' => 'Dispatched', 'color' => 'primary'],
                                            'on_scene' => ['label' => 'In Progress', 'color' => 'warning'],
                                            'completed' => ['label' => 'Resolved', 'color' => 'success'],
                                            'cancelled' => ['label' => 'Cancelled', 'color' => 'danger']
                                        ];
                                        $status = $primaryResponder ? ($statusMap[$primaryResponder->status] ?? ['label' => ucfirst($report->status), 'color' => 'secondary']) : ['label' => 'Pending', 'color' => 'secondary'];
                                    @endphp
                                    <span class="badge bg-{{ $status['color'] }}">{{ $status['label'] }}</span>
                                @else
                                    <span class="badge bg-secondary">Pending</span>
                                @endif
                            </p>
                        </div>
                        <div class="col-md-4">
                            <strong>Reported At:</strong>
                            <p>{{ $report->created_at->format('M d, Y g:i:s A') }}</p>
                        </div>
                        <div class="col-12">
                            <strong>Description:</strong>
                            <p>{{ $report->description ?? 'No description provided' }}</p>
                        </div>
                        <div class="col-12">
                            <strong>Address:</strong>
                            <p>{{ $report->address ?? 'No address provided' }}</p>
                        </div>
                        @if($report->landmark)
                        <div class="col-12">
                            <strong>Landmark:</strong>
                            <p>{{ $report->landmark }}</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Contact Information -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-person-lines-fill me-2"></i>Contact Information</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <strong>Contact Name:</strong>
                            <p>{{ $report->contact_name ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-6">
                            <strong>Contact Number:</strong>
                            <p>{{ $report->contact_number ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-6">
                            <strong>Email:</strong>
                            <p>{{ $report->email ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-6">
                            <strong>Reported By:</strong>
                            <p>{{ $report->user->name ?? 'Anonymous' }}</p>
                        </div>
                    </div>
                </div>
            </div>


            <!-- Media Files -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-images me-2"></i>Media Files</h5>
                    @auth
                    <button class="btn btn-sm btn-primary" onclick="showMediaUpload()">
                        <i class="bi bi-plus-circle me-1"></i>Upload
                    </button>
                    @endauth
                </div>
                <div class="card-body">
                    <div id="mediaContainer" class="row g-2">
                        <div class="col-12 text-center">
                            <div class="spinner-border spinner-border-sm" role="status"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Multi-Responder Assignment -->
            @if(auth()->user()->isAdmin() || auth()->user()->isResponder())
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-people me-2"></i>Assigned Responders</h5>
                    @if(auth()->user()->isAdmin() || $report->assigned_responder_id === auth()->id())
                    <button class="btn btn-sm btn-primary" onclick="showAssignResponder()">
                        <i class="bi bi-plus-circle me-1"></i>Assign
                    </button>
                    @endif
                </div>
                <div class="card-body">
                    <div id="respondersContainer">
                        <div class="text-center">
                            <div class="spinner-border spinner-border-sm" role="status"></div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Used Resources -->
            @if($resourceUsageLogs && $resourceUsageLogs->count() > 0)
            <div class="col-md-12 mb-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-truck me-2"></i>Used Resources</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            @foreach($resourceUsageLogs as $log)
                            <div class="col-md-6 col-lg-4">
                                <div class="card h-100 border">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div>
                                                <h6 class="mb-1">{{ $log->resource->name ?? 'Unknown Resource' }}</h6>
                                                <p class="text-muted small mb-0">
                                                    <i class="bi bi-tag me-1"></i>{{ ucfirst($log->resource->type ?? 'N/A') }}
                                                    @if($log->resource->identifier)
                                                        | {{ $log->resource->identifier }}
                                                    @endif
                                                </p>
                                            </div>
                                            <span class="badge bg-{{ $log->released_at ? 'success' : 'warning' }}">
                                                {{ $log->released_at ? 'Released' : 'In Use' }}
                                            </span>
                                        </div>
                                        
                                        @if($log->resource->description)
                                        <p class="text-muted small mb-2">{{ Str::limit($log->resource->description, 80) }}</p>
                                        @endif
                                        
                                        <div class="small text-muted">
                                            <div class="mb-1">
                                                <i class="bi bi-person me-1"></i><strong>Used by:</strong> {{ $log->responder->name ?? 'Unknown' }}
                                            </div>
                                            @if($log->assignedBy)
                                            <div class="mb-1">
                                                <i class="bi bi-person-check me-1"></i><strong>Assigned by:</strong> {{ $log->assignedBy->name }}
                                            </div>
                                            @endif
                                            <div class="mb-1">
                                                <i class="bi bi-calendar-check me-1"></i><strong>Assigned:</strong> {{ $log->assigned_at ? $log->assigned_at->format('M d, Y g:i A') : 'N/A' }}
                                            </div>
                                            @if($log->released_at)
                                            <div class="mb-1">
                                                <i class="bi bi-calendar-x me-1"></i><strong>Released:</strong> {{ $log->released_at->format('M d, Y g:i A') }}
                                            </div>
                                            <div class="mb-1">
                                                <i class="bi bi-clock me-1"></i><strong>Duration:</strong> {{ $log->assigned_at ? $log->assigned_at->diffForHumans($log->released_at, true) : 'N/A' }}
                                            </div>
                                            @endif
                                            @if($log->notes)
                                            <div class="mt-2">
                                                <strong>Notes:</strong> {{ $log->notes }}
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            @endif

        </div>

        <!-- Sidebar -->
        <div class="col-md-4">
            <!-- Messages -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body text-center">
                    <a href="{{ route('messages.index') }}?report_id={{ $report->id }}" class="btn btn-primary btn-sm w-100">
                        <i class="bi bi-chat-dots me-2"></i>Messages
                        @if($unreadMessageCount > 0)
                            <span class="badge bg-danger ms-2" id="messageCountBadge">{{ $unreadMessageCount }}</span>
                        @else
                            <span class="badge bg-secondary ms-2" id="messageCountBadge" style="display: none;">0</span>
                        @endif
                    </a>
                </div>
            </div>
            <!-- Location -->
            @if($report->latitude && $report->longitude)
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-geo-alt me-2"></i>Location</h5>
                </div>
                <div class="card-body">
                    <div id="reportMap" style="height: 300px; width: 100%;"></div>
                    <div class="mt-3">
                        <a href="https://www.google.com/maps?q={{ $report->latitude }},{{ $report->longitude }}" target="_blank" class="btn btn-outline-primary btn-sm w-100">
                            <i class="bi bi-map me-2"></i>Open in Google Maps
                        </a>
                    </div>
                </div>
            </div>
            @endif

            <!-- Responders Details and Timelines -->
            @if($allResponders && $allResponders->count() > 0)
                @foreach($allResponders as $responderAssignment)
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">
                            <i class="bi bi-person-badge me-2"></i>
                            {{ $responderAssignment->responder->name ?? 'Unknown Responder' }}
                            <span class="badge bg-{{ $responderAssignment->role === 'primary' ? 'danger' : 'secondary' }} ms-2">
                                {{ ucfirst($responderAssignment->role) }}
                            </span>
                            @php
                                $statusColors = [
                                    'assigned' => 'info',
                                    'en_route' => 'primary',
                                    'on_scene' => 'warning',
                                    'completed' => 'success',
                                    'cancelled' => 'danger'
                                ];
                                $statusColor = $statusColors[$responderAssignment->status] ?? 'secondary';
                                $statusLabels = [
                                    'assigned' => 'Assigned',
                                    'en_route' => 'En Route',
                                    'on_scene' => 'On Scene',
                                    'completed' => 'Completed',
                                    'cancelled' => 'Cancelled'
                                ];
                                $statusLabel = $statusLabels[$responderAssignment->status] ?? ucfirst($responderAssignment->status);
                            @endphp
                            <span class="badge bg-{{ $statusColor }} ms-2">{{ $statusLabel }}</span>
                        </h6>
                    </div>
                    <div class="card-body">
                        <!-- Responder Details -->
                        <div class="row mb-4">
                            @if($responderAssignment->responder && $responderAssignment->responder->responderDetail)
                                @if($responderAssignment->responder->responderDetail->department)
                                <div class="col-md-6 mb-2">
                                    <strong>Department:</strong>
                                    <p class="mb-0">{{ $responderAssignment->responder->responderDetail->department }}</p>
                                </div>
                                @endif
                                @if($responderAssignment->responder->responderDetail->vehicle_type)
                                <div class="col-md-6 mb-2">
                                    <strong>Vehicle Type:</strong>
                                    <p class="mb-0">{{ $responderAssignment->responder->responderDetail->vehicle_type }}</p>
                                </div>
                                @endif
                                @if($responderAssignment->responder->responderDetail->station_address)
                                <div class="col-md-6 mb-2">
                                    <strong>Station Address:</strong>
                                    <p class="mb-0">{{ $responderAssignment->responder->responderDetail->station_address }}</p>
                                </div>
                                @endif
                                @if($responderAssignment->responder->responderDetail->license_number)
                                <div class="col-md-6 mb-2">
                                    <strong>License Number:</strong>
                                    <p class="mb-0">{{ $responderAssignment->responder->responderDetail->license_number }}</p>
                                </div>
                                @endif
                            @elseif($responderAssignment->responding_unit)
                                <div class="col-md-6 mb-2">
                                    <strong>Responding Unit:</strong>
                                    <p class="mb-0">{{ $responderAssignment->responding_unit }}</p>
                                </div>
                            @endif
                            @if($responderAssignment->response_notes)
                            <div class="col-12 mb-2">
                                <strong>Response Notes:</strong>
                                <p class="mb-0">{!! nl2br(e($responderAssignment->response_notes)) !!}</p>
                            </div>
                            @endif
                        </div>

                        <!-- Responder Timeline -->
                        <h6 class="mb-3"><i class="bi bi-clock-history me-2"></i>Response Timeline</h6>
                        <div class="timeline">
                            <div class="timeline-item">
                                <div class="timeline-marker bg-primary"></div>
                                <div class="timeline-content">
                                    <strong>Report Created</strong>
                                    <p class="text-muted mb-0">{{ $report->created_at->format('M d, Y g:i:s A') }}</p>
                                </div>
                            </div>
                            
                            @if($responderAssignment->assigned_at)
                            <div class="timeline-item">
                                <div class="timeline-marker bg-info"></div>
                                <div class="timeline-content">
                                    <strong>Assigned</strong>
                                    <p class="text-muted mb-0">{{ $responderAssignment->assigned_at->format('M d, Y g:i:s A') }}</p>
                                    @if($report->created_at)
                                        <small class="text-success">Response time: {{ $report->created_at->diffForHumans($responderAssignment->assigned_at, true) }}</small>
                                    @endif
                                </div>
                            </div>
                            @endif

                            @if($responderAssignment->en_route_at)
                            <div class="timeline-item">
                                <div class="timeline-marker bg-primary"></div>
                                <div class="timeline-content">
                                    <strong>En Route</strong>
                                    <p class="text-muted mb-0">{{ $responderAssignment->en_route_at->format('M d, Y g:i:s A') }}</p>
                                    @if($responderAssignment->assigned_at)
                                        <small class="text-success">Time: {{ $responderAssignment->assigned_at->diffForHumans($responderAssignment->en_route_at, true) }}</small>
                                    @endif
                                </div>
                            </div>
                            @endif

                            @if($responderAssignment->on_scene_at)
                            <div class="timeline-item">
                                <div class="timeline-marker bg-warning"></div>
                                <div class="timeline-content">
                                    <strong>On Scene</strong>
                                    <p class="text-muted mb-0">{{ $responderAssignment->on_scene_at->format('M d, Y g:i:s A') }}</p>
                                    @if($responderAssignment->en_route_at)
                                        <small class="text-success">Time: {{ $responderAssignment->en_route_at->diffForHumans($responderAssignment->on_scene_at, true) }}</small>
                                    @endif
                                </div>
                            </div>
                            @endif

                            @if($responderAssignment->completed_at)
                            <div class="timeline-item">
                                <div class="timeline-marker bg-success"></div>
                                <div class="timeline-content">
                                    <strong>Completed</strong>
                                    <p class="text-muted mb-0">{{ $responderAssignment->completed_at->format('M d, Y g:i:s A') }}</p>
                                    @if($responderAssignment->assigned_at)
                                        <small class="text-success">Total time: {{ $responderAssignment->assigned_at->diffForHumans($responderAssignment->completed_at, true) }}</small>
                                    @endif
                                </div>
                            </div>
                            @endif

                            @if($responderAssignment->cancelled_at)
                            <div class="timeline-item">
                                <div class="timeline-marker bg-danger"></div>
                                <div class="timeline-content">
                                    <strong>Cancelled</strong>
                                    <p class="text-muted mb-0">{{ $responderAssignment->cancelled_at->format('M d, Y g:i:s A') }}</p>
                                    @if($responderAssignment->assigned_at)
                                        <small class="text-danger">Time from assignment: {{ $responderAssignment->assigned_at->diffForHumans($responderAssignment->cancelled_at, true) }}</small>
                                    @endif
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            @else
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body text-center py-4">
                    <i class="bi bi-person-x fs-1 text-muted"></i>
                    <p class="text-muted mt-3 mb-0">No responders assigned yet</p>
                </div>
            </div>
            @endif

        </div>
    </div>
</div>

<!-- Edit Report Modal -->
@if(auth()->check() && (auth()->id() === $report->user_id || auth()->user()->isAdmin()))
    @php
        // Check if no responders assigned or status is pending
        $hasResponders = $report->responders && $report->responders->count() > 0;
        $isPending = $report->status === 'pending';
        $isAdmin = auth()->user()->isAdmin();
        // Admins can always edit, regular users can only edit if no responders or pending
        $canEdit = $isAdmin || !$hasResponders || $isPending;
    @endphp
    @if($canEdit)
<div class="modal fade" id="editReportModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title">Edit Report #{{ $report->id }}</h5>
                <button type="button" class="btn border-0 bg-transparent" data-bs-dismiss="modal" aria-label="Close">
                    <i class="bi bi-x-lg fs-12 text-white"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="editReportForm" method="POST" action="{{ route('reports.update', $report->id) }}">
                    @csrf
                    @method('PATCH')

                    <!-- Emergency Type -->
                    <div class="mb-3">
                        <div class="form-floating">
                            <select name="type" id="edit_type" class="form-select form-select-sm" required aria-label="Type of Emergency">
                                <option value="">-- Select Type --</option>
                                @foreach (\App\Models\EmergencyType::where('status', true)->orderBy('name')->get() as $type)
                                    <option value="{{ $type->code }}" {{ $report->type === $type->code ? 'selected' : '' }}>
                                        {{ $type->name }} | {{ $type->description }}
                                    </option>
                                @endforeach
                            </select>
                            <label for="edit_type">Type of Emergency</label>
                        </div>
                    </div>

                    <!-- Severity Level -->
                    <div class="mb-3">
                        <div class="form-floating">
                            <select name="severity_level" id="edit_severity_level" class="form-select form-select-sm" required aria-label="Severity Level">
                                <option value="">-- Select Level --</option>
                                <option value="low" {{ $report->severity_level === 'low' ? 'selected' : '' }}>Low</option>
                                <option value="moderate" {{ $report->severity_level === 'moderate' ? 'selected' : '' }}>Moderate</option>
                                <option value="high" {{ $report->severity_level === 'high' ? 'selected' : '' }}>High</option>
                                <option value="critical" {{ $report->severity_level === 'critical' ? 'selected' : '' }}>Critical</option>
                            </select>
                            <label for="edit_severity_level">Severity Level</label>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="mb-3">
                        <div class="form-floating">
                            <textarea class="form-control form-control-sm" placeholder="Leave a description here" id="edit_description" name="description" style="height: 100px">{{ $report->description }}</textarea>
                            <label for="edit_description">Description</label>
                        </div>
                    </div>

                    <!-- Contact Info -->
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control form-control-sm" id="edit_contact_name" name="contact_name" placeholder="Contact Name" value="{{ $report->contact_name }}" required>
                        <label for="edit_contact_name">Contact Name</label>
                    </div>

                    <div class="form-floating mb-3">
                        <input 
                            type="tel" 
                            class="form-control form-control-sm" 
                            id="edit_contact_number" 
                            name="contact_number" 
                            placeholder="09XXXXXXXXX"
                            pattern="^09\d{9}$"
                            maxlength="11"
                            value="{{ $report->contact_number }}"
                            required
                        >
                        <label for="edit_contact_number">Contact Number</label>
                    </div>

                    <!-- Map -->
                    @if($report->latitude && $report->longitude)
                    <div class="mb-3">
                        <label for="map_edit" class="form-label">Update Location</label>
                        <div id="map_edit" style="height: 300px; width: 100%; border-radius: 8px;"></div>
                        <small class="text-muted">
                            Click anywhere on the map or drag the marker to update the location.
                        </small>
                    </div>
                    @endif

                    <!-- Address -->
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control form-control-sm" id="edit_address" name="address" placeholder="Address" value="{{ $report->address }}">
                        <label for="edit_address">Address</label>
                    </div>

                    <!-- Hidden Inputs -->
                    <input type="hidden" name="latitude" id="edit_latitude" value="{{ $report->latitude }}">
                    <input type="hidden" name="longitude" id="edit_longitude" value="{{ $report->longitude }}">

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning btn-sm">Update Report</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
    @endif
@endif

<!-- Media Viewer Modal -->
<div class="modal fade" id="mediaViewerModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0" style="background: rgba(255, 255, 255, 0.5); backdrop-filter: blur(10px);">
            <div class="modal-header" style="background: rgba(255, 255, 255, 0.5); backdrop-filter: blur(10px);">
                <h5 class="modal-title" id="mediaViewerTitle">
                    <i class="bi bi-images me-2"></i>Media Viewer
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0 position-relative" style="min-height: 500px; background: rgba(255, 255, 255, 0.5); backdrop-filter: blur(10px);">
                <button id="prevMediaBtn" class="btn btn-light position-absolute top-50 start-0 translate-middle-y ms-3" style="z-index: 10; border-radius: 50%; width: 50px; height: 50px; display: none; box-shadow: 0 2px 10px rgba(0,0,0,0.3); background: rgba(255, 255, 255, 0.9);" title="Previous (←)">
                    <i class="bi bi-chevron-left fs-5"></i>
                </button>
                <button id="nextMediaBtn" class="btn btn-light position-absolute top-50 end-0 translate-middle-y me-3" style="z-index: 10; border-radius: 50%; width: 50px; height: 50px; display: none; box-shadow: 0 2px 10px rgba(0,0,0,0.3); background: rgba(255, 255, 255, 0.9);" title="Next (→)">
                    <i class="bi bi-chevron-right fs-5"></i>
                </button>
                <div id="mediaViewerContent" class="d-flex align-items-center justify-content-center p-4" style="min-height: 500px; background: rgba(248, 249, 250, 0.5);">
                    <!-- Media will be loaded here -->
                </div>
                <div class="text-center p-3 border-top" style="background: rgba(255, 255, 255, 0.5); backdrop-filter: blur(10px);">
                    <small id="mediaViewerCounter" class="fs-6 text-muted">1 / 1</small>
                    <div class="mt-2">
                        <small class="text-muted">Use arrow keys or click buttons to navigate</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if($report->latitude && $report->longitude)
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var localStyleId = localStorage.getItem("selectedMapStyle") || "openStreet_1";
        var localSelectedStyle = mapStyles.find(style => style.id === localStyleId) || mapStyles[0];
        
        var reportMap = L.map("reportMap", { 
            zoomControl: true, 
            attributionControl: false 
        }).setView([{{ $report->latitude }}, {{ $report->longitude }}], 14);
        
        L.tileLayer(localSelectedStyle.url, {
            attribution: localSelectedStyle.attribution || '&copy; OpenStreetMap contributors'
        }).addTo(reportMap);

        var reportType = "{{ strtolower($report->type) }}";
        var reportSeverity = "{{ strtolower($report->severity_level ?? 'moderate') }}";
        
        var reportMarker = L.marker([{{ $report->latitude }}, {{ $report->longitude }}], {
            icon: createCustomMarker(reportType, reportSeverity)
        }).addTo(reportMap);
        
        reportMarker.bindPopup('Emergency Location', {
            offset: [0, -25],
        }).openPopup();
    });
</script>
@endif

<style>
.timeline {
    position: relative;
    padding-left: 30px;
}
.timeline-item {
    position: relative;
    padding-bottom: 20px;
}
.timeline-marker {
    position: absolute;
    left: -25px;
    top: 5px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
}
.timeline-content {
    padding-left: 10px;
}
.timeline-item:not(:last-child)::before {
    content: '';
    position: absolute;
    left: -20px;
    top: 17px;
    width: 2px;
    height: calc(100% - 5px);
    background: #dee2e6;
}
</style>

<script>
const reportId = {{ $report->id }};
// currentUserId is already declared in the layout file, so we don't redeclare it here
const isAdmin = {{ auth()->user()->isAdmin() ? 'true' : 'false' }};

// Load media on page load
document.addEventListener('DOMContentLoaded', function() {
    loadMedia();
    loadResponders();
    // Check responders and update delete button state
    checkRespondersAndUpdateDeleteButton();
});

// Media Functions
let allMedia = [];
let currentMediaIndex = 0;

async function loadMedia() {
    try {
        const response = await fetch(`/reports/${reportId}/media`);
        const data = await response.json();
        const container = document.getElementById('mediaContainer');
        
        if (data.status === 'success' && data.media.length > 0) {
            allMedia = data.media; // Store all media for modal navigation
            container.innerHTML = '';
            data.media.forEach((media, index) => {
                const col = document.createElement('div');
                col.className = 'col-md-3 col-6 mb-3';
                
                let content = '';
                const mediaUrl = media.url || `/storage/${media.file_path}`;
                const thumbnailUrl = media.thumbnail_url || (media.thumbnail_path ? `/storage/${media.thumbnail_path}` : mediaUrl);
                
                if (media.file_type === 'image') {
                    const canDelete = media.user_id === currentUserId || isAdmin;
                    const deleteButton = canDelete 
                        ? `<button class="btn btn-sm btn-danger w-100" onclick="event.stopPropagation(); deleteMedia(${media.id})">
                            <i class="bi bi-trash me-1"></i> Delete
                           </button>`
                        : `<button class="btn btn-sm btn-secondary w-100" disabled title="You can only delete files you uploaded">
                            <i class="bi bi-trash me-1"></i> Delete
                           </button>`;
                    content = `
                        <div class="card border shadow-sm" style="overflow: hidden;">
                            <div style="width: 100%; height: 100px; overflow: hidden; background: #f8f9fa; display: flex; align-items: center; justify-content: center; cursor: pointer;" onclick="openMediaViewer(${index})">
                                <img src="${thumbnailUrl}" 
                                     class="card-img-top" 
                                     style="width: 100%; height: 100%; object-fit: cover;" 
                                     onerror="this.onerror=null; this.src='${mediaUrl}';"
                                     loading="lazy">
                            </div>
                            <div class="card-body p-2">
                                ${deleteButton}
                            </div>
                        </div>
                    `;
                } else if (media.file_type === 'video') {
                    const canDelete = media.user_id === currentUserId || isAdmin;
                    const deleteButton = canDelete 
                        ? `<button class="btn btn-sm btn-danger w-100" onclick="event.stopPropagation(); deleteMedia(${media.id})">
                            <i class="bi bi-trash me-1"></i> Delete
                           </button>`
                        : `<button class="btn btn-sm btn-secondary w-100" disabled title="You can only delete files you uploaded">
                            <i class="bi bi-trash me-1"></i> Delete
                           </button>`;
                    content = `
                        <div class="card border shadow-sm" style="overflow: hidden; position: relative;">
                            <div style="width: 100%; height: 100px; overflow: hidden; background: #000; position: relative; cursor: pointer;" onclick="openMediaViewer(${index})">
                                <video src="${mediaUrl}" 
                                       style="width: 100%; height: 100%; object-fit: cover;"
                                       muted
                                       onmouseover="this.play()"
                                       onmouseout="this.pause(); this.currentTime=0;">
                                </video>
                                <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); pointer-events: none;">
                                    <i class="bi bi-play-circle-fill text-white" style="font-size: 2.5rem; text-shadow: 0 0 10px rgba(0,0,0,0.8);"></i>
                                </div>
                                <div style="position: absolute; bottom: 5px; right: 5px;">
                                    <span class="badge bg-dark bg-opacity-75">
                                        <i class="bi bi-play-circle"></i> Video
                                    </span>
                                </div>
                            </div>
                            <div class="card-body p-2">
                                ${deleteButton}
                            </div>
                        </div>
                    `;
                } else {
                    const canDelete = media.user_id === currentUserId || isAdmin;
                    const deleteButton = canDelete 
                        ? `<button class="btn btn-sm btn-danger w-100" onclick="deleteMedia(${media.id})">
                            <i class="bi bi-trash me-1"></i> Delete
                           </button>`
                        : `<button class="btn btn-sm btn-secondary w-100" disabled title="You can only delete files you uploaded">
                            <i class="bi bi-trash me-1"></i> Delete
                           </button>`;
                    content = `
                        <div class="card border shadow-sm">
                            <div class="card-body text-center p-3" style="height: 100px; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                                <i class="bi bi-file-earmark fs-1 text-muted"></i>
                                <small class="text-muted d-block mt-2 text-truncate" style="max-width: 100%;" title="${media.file_path}">
                                    ${media.file_path.split('/').pop()}
                                </small>
                            </div>
                            <div class="card-body p-2">
                                <a href="${mediaUrl}" target="_blank" class="btn btn-sm btn-primary w-100 mb-1">
                                    <i class="bi bi-eye me-1"></i> View
                                </a>
                                ${deleteButton}
                            </div>
                        </div>
                    `;
                }
                col.innerHTML = content;
                container.appendChild(col);
            });
        } else {
            container.innerHTML = '<div class="col-12 text-muted text-center py-4"><i class="bi bi-images fs-1 d-block mb-2"></i>No media files uploaded</div>';
        }
    } catch (error) {
        console.error('Error loading media:', error);
        container.innerHTML = '<div class="col-12 text-danger text-center py-4">Error loading media files</div>';
    }
}

function openMediaViewer(index) {
    if (allMedia.length === 0) return;
    
    currentMediaIndex = index;
    const modalElement = document.getElementById('mediaViewerModal');
    const modal = new bootstrap.Modal(modalElement);
    
    // Clear content when modal is hidden
    modalElement.addEventListener('hidden.bs.modal', function() {
        const currentVideo = document.querySelector('#mediaViewerContent video');
        if (currentVideo) {
            currentVideo.pause();
            currentVideo.currentTime = 0;
        }
    }, { once: true });
    
    updateMediaViewer();
    modal.show();
}

function updateMediaViewer() {
    if (allMedia.length === 0) return;
    
    const media = allMedia[currentMediaIndex];
    const mediaUrl = media.url || `/storage/${media.file_path}`;
    const content = document.getElementById('mediaViewerContent');
    const counter = document.getElementById('mediaViewerCounter');
    const prevBtn = document.getElementById('prevMediaBtn');
    const nextBtn = document.getElementById('nextMediaBtn');
    
    // Update counter
    counter.textContent = `${currentMediaIndex + 1} / ${allMedia.length}`;
    
    // Show/hide navigation buttons
    prevBtn.style.display = allMedia.length > 1 ? 'block' : 'none';
    nextBtn.style.display = allMedia.length > 1 ? 'block' : 'none';
    
    // Load media content
    if (media.file_type === 'image') {
        content.innerHTML = `
            <img src="${mediaUrl}" 
                 class="img-fluid shadow-sm" 
                 style="max-width: 100%; max-height: 70vh; object-fit: contain; border-radius: 8px;"
                 onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\'text-center text-muted\'><i class=\'bi bi-image fs-1\'></i><br><p class=\'mt-2\'>Image not available</p></div>';"
                 alt="Media image">
        `;
    } else if (media.file_type === 'video') {
        content.innerHTML = `
            <video src="${mediaUrl}" 
                   controls 
                   class="w-100 shadow-sm" 
                   style="max-height: 70vh; border-radius: 8px;"
                   preload="metadata"
                   onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\'text-center text-muted\'><i class=\'bi bi-play-circle fs-1\'></i><br><p class=\'mt-2\'>Video not available</p></div>';">
                Your browser does not support the video tag.
            </video>
        `;
    } else {
        content.innerHTML = `
            <div class="text-center">
                <i class="bi bi-file-earmark fs-1 text-muted"></i>
                <p class="mt-3 text-muted">${media.file_path.split('/').pop()}</p>
                <a href="${mediaUrl}" target="_blank" class="btn btn-primary btn-sm">
                    <i class="bi bi-download me-1"></i> Download File
                </a>
            </div>
        `;
    }
}

function showPreviousMedia() {
    if (allMedia.length === 0) return;
    // Stop any playing videos
    const currentVideo = document.querySelector('#mediaViewerContent video');
    if (currentVideo) {
        currentVideo.pause();
        currentVideo.currentTime = 0;
    }
    currentMediaIndex = (currentMediaIndex - 1 + allMedia.length) % allMedia.length;
    updateMediaViewer();
}

function showNextMedia() {
    if (allMedia.length === 0) return;
    // Stop any playing videos
    const currentVideo = document.querySelector('#mediaViewerContent video');
    if (currentVideo) {
        currentVideo.pause();
        currentVideo.currentTime = 0;
    }
    currentMediaIndex = (currentMediaIndex + 1) % allMedia.length;
    updateMediaViewer();
}

// Keyboard navigation
document.addEventListener('keydown', function(e) {
    const modal = document.getElementById('mediaViewerModal');
    if (modal.classList.contains('show')) {
        if (e.key === 'ArrowLeft') {
            showPreviousMedia();
        } else if (e.key === 'ArrowRight') {
            showNextMedia();
        } else if (e.key === 'Escape') {
            bootstrap.Modal.getInstance(modal).hide();
        }
    }
});

// Set up navigation buttons (after DOM is ready)
document.addEventListener('DOMContentLoaded', function() {
    const prevBtn = document.getElementById('prevMediaBtn');
    const nextBtn = document.getElementById('nextMediaBtn');
    if (prevBtn) prevBtn.addEventListener('click', showPreviousMedia);
    if (nextBtn) nextBtn.addEventListener('click', showNextMedia);
});

function showMediaUpload() {
    const input = document.createElement('input');
    input.type = 'file';
    input.multiple = true;
    input.accept = 'image/*,video/*';
    input.onchange = async function(e) {
        const files = e.target.files;
        if (files.length === 0) return;
        
        // Validate files before upload
        const validFiles = [];
        const errors = [];
        const videoMaxSize = 50 * 1024 * 1024; // 50MB
        const imageMaxSize = 10 * 1024 * 1024; // 10MB
        
        Array.from(files).forEach((file) => {
            const isImage = file.type.startsWith('image/');
            const isVideo = file.type.startsWith('video/');
            
            if (!isImage && !isVideo) {
                errors.push(`${file.name}: Only images and videos are allowed`);
                return;
            }
            
            const maxSize = isVideo ? videoMaxSize : imageMaxSize;
            if (file.size > maxSize) {
                const maxMB = isVideo ? 50 : 10;
                errors.push(`${file.name}: File too large (max ${maxMB}MB for ${isVideo ? 'videos' : 'images'})`);
                return;
            }
            
            validFiles.push(file);
        });
        
        if (errors.length > 0) {
            Swal.fire({
                icon: 'error',
                title: 'Validation Errors',
                html: errors.join('<br>'),
                confirmButtonText: 'OK'
            });
            if (validFiles.length === 0) return;
        }
        
        if (validFiles.length === 0) return;
        
        const formData = new FormData();
        validFiles.forEach((file, index) => {
            formData.append(`files[${index}]`, file);
        });
        
        try {
            Swal.fire({
                title: 'Uploading...',
                text: 'Please wait while your file is being uploaded.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            const response = await fetch(`/reports/${reportId}/media`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: formData
            });
            
            // Check if response is JSON
            const contentType = response.headers.get('content-type');
            let data;
            
            if (contentType && contentType.includes('application/json')) {
                data = await response.json();
            } else {
                // If not JSON, get text and try to parse
                const text = await response.text();
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    // If it's HTML error page, show generic error
                    Swal.fire({
                        icon: 'error',
                        title: 'Upload Failed',
                        html: response.status === 422 
                            ? 'Validation error. Please check file types and sizes.' 
                            : response.status === 403
                            ? 'You do not have permission to upload files.'
                            : response.status === 404
                            ? 'Report not found.'
                            : 'Server error occurred. Please try again later.',
                        confirmButtonText: 'OK'
                    });
                    console.error('Non-JSON response:', text.substring(0, 200));
                    return;
                }
            }
            
            if (response.ok && data.status === 'success') {
                loadMedia();
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: 'Files uploaded successfully!',
                    timer: 2000,
                    showConfirmButton: false
                });
            } else {
                // Handle validation errors
                let errorMsg = 'Unknown error';
                if (data.message) {
                    errorMsg = data.message;
                } else if (data.error) {
                    errorMsg = typeof data.error === 'object' ? JSON.stringify(data.error) : data.error;
                } else if (data.errors) {
                    // Laravel validation errors
                    const errorMessages = [];
                    Object.keys(data.errors).forEach(key => {
                        errorMessages.push(...data.errors[key]);
                    });
                    errorMsg = errorMessages.join('<br>');
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'Upload Failed',
                    html: errorMsg,
                    confirmButtonText: 'OK'
                });
                console.error('Upload error:', data);
            }
        } catch (error) {
            console.error('Upload error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error uploading files: ' + error.message,
                confirmButtonText: 'OK'
            });
        }
    };
    input.click();
}

async function deleteMedia(mediaId) {
    const result = await Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    });
    
    if (!result.isConfirmed) return;
    
    try {
        const response = await fetch(`/media/${mediaId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        });
        
        // Check if response is JSON
        const contentType = response.headers.get('content-type');
        let data;
        
        if (contentType && contentType.includes('application/json')) {
            data = await response.json();
        } else {
            const text = await response.text();
            try {
                data = JSON.parse(text);
            } catch (e) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Server error occurred. Please try again later.',
                    confirmButtonText: 'OK'
                });
                return;
            }
        }
        
        if (response.ok && data.status === 'success') {
            loadMedia();
            Swal.fire({
                icon: 'success',
                title: 'Deleted!',
                text: 'File has been deleted.',
                timer: 2000,
                showConfirmButton: false
            });
        } else {
            // Handle permission errors
            const errorMessage = data.message || 'Failed to delete file';
            Swal.fire({
                icon: 'error',
                title: 'Cannot Delete',
                text: errorMessage,
                confirmButtonText: 'OK'
            });
        }
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Error deleting file: ' + error.message,
            confirmButtonText: 'OK'
        });
    }
}

// Check if report has responders and update delete button visibility
async function checkRespondersAndUpdateDeleteButton() {
    try {
        const deleteBtn = document.getElementById('deleteReportBtn');
        if (!deleteBtn) return;
        
        // Admins can always see the delete button (backend will handle validation)
        if (isAdmin) {
            deleteBtn.style.display = 'inline-block';
            return;
        }
        
        const response = await fetch(`/reports/${reportId}/responders`);
        const data = await response.json();
        
        if (data.status === 'success' && data.responders && data.responders.length > 0) {
            // Has responders - hide delete button for regular users
            deleteBtn.style.display = 'none';
        } else {
            // No responders - show delete button
            deleteBtn.style.display = 'inline-block';
        }
    } catch (error) {
        console.error('Error checking responders:', error);
    }
}

// Responder Functions
async function loadResponders() {
    try {
        const container = document.getElementById('respondersContainer');
        
        // Check if container exists (only visible for admins/responders)
        if (!container) {
            // Still check for delete button even if container doesn't exist
            checkRespondersAndUpdateDeleteButton();
            return;
        }
        
        const response = await fetch(`/reports/${reportId}/responders`);
        const data = await response.json();
        
        if (data.status === 'success' && data.responders.length > 0) {
            let html = '<div class="table-responsive"><table class="table table-sm table-hover mb-0">';
            html += '<thead><tr><th>Responder</th><th>Role</th><th>Status</th></tr></thead><tbody>';
            data.responders.forEach(assignment => {
                const statusColors = {
                    'assigned': 'info',
                    'en_route': 'primary',
                    'on_scene': 'warning',
                    'completed': 'success',
                    'cancelled': 'danger'
                };
                const statusColor = statusColors[assignment.status] || 'secondary';
                const statusLabels = {
                    'assigned': 'Assigned',
                    'en_route': 'En Route',
                    'on_scene': 'On Scene',
                    'completed': 'Completed',
                    'cancelled': 'Cancelled'
                };
                const statusLabel = statusLabels[assignment.status] || assignment.status;
                const roleBadge = assignment.role === 'primary' ? 'danger' : 'secondary';
                html += `
                    <tr>
                        <td><strong>${assignment.responder.name}</strong></td>
                        <td><span class="badge bg-${roleBadge}">${assignment.role.charAt(0).toUpperCase() + assignment.role.slice(1)}</span></td>
                        <td><span class="badge bg-${statusColor}">${statusLabel}</span></td>
                    </tr>
                `;
            });
            html += '</tbody></table></div>';
            container.innerHTML = html;
            
            // Update delete button state
            checkRespondersAndUpdateDeleteButton();
        } else {
            container.innerHTML = '<p class="text-muted text-center mb-0">No responders assigned</p>';
            
            // Update delete button state
            checkRespondersAndUpdateDeleteButton();
        }
    } catch (error) {
        console.error('Error loading responders:', error);
        const container = document.getElementById('respondersContainer');
        if (container) {
            container.innerHTML = '<p class="text-danger text-center mb-0">Error loading responders</p>';
        }
        // Still try to update delete button
        checkRespondersAndUpdateDeleteButton();
    }
}

async function showAssignResponder() {
    // First, get the current responders to check if primary exists
    let hasPrimary = false;
    try {
        const response = await fetch(`/reports/${reportId}/responders`);
        const data = await response.json();
        if (data.status === 'success' && data.responders) {
            hasPrimary = data.responders.some(r => r.role === 'primary');
        }
    } catch (error) {
        console.error('Error checking responders:', error);
    }
    
    const roleToAssign = hasPrimary ? 'secondary' : 'primary';
    const roleLabel = hasPrimary ? 'Secondary' : 'Primary';
    
    // Get responder ID input
    const inputResult = await Swal.fire({
        title: 'Assign Responder',
        input: 'number',
        inputLabel: 'Enter Responder User ID',
        inputPlaceholder: 'Enter User ID',
        showCancelButton: true,
        confirmButtonText: 'Next',
        cancelButtonText: 'Cancel',
        inputValidator: (value) => {
            if (!value) {
                return 'You need to enter a User ID!';
            }
        }
    });
    
    if (!inputResult.isConfirmed || !inputResult.value) return;
    
    const responderId = inputResult.value;
    
    // Show confirmation alert
    const confirmResult = await Swal.fire({
        title: 'Confirm Assignment',
        html: `
            <p>Assign responder <strong>#${responderId}</strong> as <strong>${roleLabel}</strong> responder?</p>
            ${hasPrimary ? '<p class="text-muted small">A primary responder already exists, so this will be assigned as secondary.</p>' : '<p class="text-info small">No primary responder exists, so this will be assigned as primary.</p>'}
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, assign!',
        cancelButtonText: 'Cancel'
    });
    
    if (!confirmResult.isConfirmed) return;
    
    // Proceed with assignment
    try {
        const response = await fetch(`/reports/${reportId}/responders/assign`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                responder_id: parseInt(responderId),
                role: roleToAssign
            })
        });
        
        // Check if response is JSON
        const contentType = response.headers.get('content-type');
        let data;
        
        if (contentType && contentType.includes('application/json')) {
            data = await response.json();
        } else {
            const text = await response.text();
            try {
                data = JSON.parse(text);
            } catch (e) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    html: response.status === 422 
                        ? 'Validation error. Please check the responder ID.' 
                        : response.status === 403
                        ? 'You do not have permission to assign responders.'
                        : response.status === 404
                        ? 'Report not found.'
                        : 'Server error occurred. Please try again later.',
                    confirmButtonText: 'OK'
                });
                return;
            }
        }
        
        if (response.ok && data.status === 'success') {
            loadResponders();
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: `Responder assigned as ${roleLabel} successfully!`,
                timer: 2000,
                showConfirmButton: false
            });
        } else {
            // Handle validation errors
            let errorMsg = data.message || 'Unknown error';
            if (data.errors) {
                const errorMessages = [];
                Object.keys(data.errors).forEach(key => {
                    errorMessages.push(...data.errors[key]);
                });
                errorMsg = errorMessages.join('<br>');
            }
            
            Swal.fire({
                icon: 'error',
                title: 'Error',
                html: errorMsg,
                confirmButtonText: 'OK'
            });
        }
    } catch (error) {
        console.error('Error assigning responder:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Error assigning responder: ' + error.message,
            confirmButtonText: 'OK'
        });
    }
}

// Edit Report Form Handler
@if(auth()->check() && (auth()->id() === $report->user_id || auth()->user()->isAdmin()))
    @php
        // Check if no responders assigned or status is pending
        $hasResponders = $report->responders && $report->responders->count() > 0;
        $isPending = $report->status === 'pending';
        $isAdmin = auth()->user()->isAdmin();
        // Admins can always edit, regular users can only edit if no responders or pending
        $canEdit = $isAdmin || !$hasResponders || $isPending;
    @endphp
    @if($canEdit)
let mapEdit = null;
let editMarker = null;

// Initialize edit map when modal is shown
document.addEventListener('DOMContentLoaded', function() {
    const editModal = document.getElementById('editReportModal');
    if (editModal) {
        editModal.addEventListener('shown.bs.modal', function() {
            @if($report->latitude && $report->longitude)
            // Small delay to ensure modal is fully rendered
            setTimeout(() => {
                initEditMap({{ $report->latitude }}, {{ $report->longitude }});
            }, 100);
            @endif
        });
    }

    const editForm = document.getElementById('editReportForm');
    if (editForm) {
        editForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const submitBtn = editForm.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = 'Updating...';
            
            // Get form data
            const formData = {
                type: document.getElementById('edit_type').value,
                severity_level: document.getElementById('edit_severity_level').value,
                description: document.getElementById('edit_description').value,
                contact_name: document.getElementById('edit_contact_name').value,
                contact_number: document.getElementById('edit_contact_number').value,
                address: document.getElementById('edit_address').value,
                latitude: document.getElementById('edit_latitude').value,
                longitude: document.getElementById('edit_longitude').value
            };
            
            try {
                const response = await fetch(editForm.action, {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });
                
                const contentType = response.headers.get('content-type');
                let data;
                
                if (contentType && contentType.includes('application/json')) {
                    data = await response.json();
                } else {
                    const text = await response.text();
                    try {
                        data = JSON.parse(text);
                    } catch (e) {
                        throw new Error('Invalid response from server');
                    }
                }
                
                if (response.ok && data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: data.message || 'Report updated successfully.',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        // Reload the page to show updated data
                        window.location.reload();
                    });
                } else {
                    let errorMsg = 'Unknown error';
                    if (data.message) {
                        errorMsg = data.message;
                    } else if (data.error) {
                        errorMsg = typeof data.error === 'object' ? JSON.stringify(data.error) : data.error;
                    } else if (data.errors) {
                        const errorMessages = [];
                        Object.keys(data.errors).forEach(key => {
                            errorMessages.push(...data.errors[key]);
                        });
                        errorMsg = errorMessages.join('<br>');
                    }
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Update Failed',
                        html: errorMsg,
                        confirmButtonText: 'OK'
                    });
                }
            } catch (error) {
                console.error('Update error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error updating report: ' + error.message,
                    confirmButtonText: 'OK'
                });
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        });
    }
});

// Initialize map for edit modal
function initEditMap(lat, lng) {
    // Get saved map style or use default
    const savedStyleId = localStorage.getItem('selectedMapStyle') || 'openStreet_1';
    const selectedStyle = typeof mapStyles !== 'undefined' 
        ? mapStyles.find(style => style.id === savedStyleId) || mapStyles[0]
        : { url: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', attribution: '&copy; OpenStreetMap contributors' };
    
    // If map already exists, just update it
    if (mapEdit) {
        mapEdit.setView([lat, lng], 15);
        if (editMarker) {
            editMarker.setLatLng([lat, lng]);
        }
        mapEdit.invalidateSize();
        setEditCoordinates(lat, lng);
        getEditAddress(lat, lng);
        return;
    }

    // Create the map
    mapEdit = L.map('map_edit', {
        attributionControl: false,
        zoomControl: true
    }).setView([lat, lng], 15);

    L.tileLayer(selectedStyle.url, {
        attribution: selectedStyle.attribution || '&copy; OpenStreetMap contributors'
    }).addTo(mapEdit);

    // Add draggable marker
    editMarker = L.marker([lat, lng], { draggable: true }).addTo(mapEdit);

    mapEdit.invalidateSize();

    // Set initial coordinates
    setEditCoordinates(lat, lng);
    getEditAddress(lat, lng);

    // Marker drag handler
    editMarker.on('dragend', function() {
        const pos = editMarker.getLatLng();
        setEditCoordinates(pos.lat, pos.lng);
        getEditAddress(pos.lat, pos.lng);
    });

    // Map click handler
    mapEdit.on('click', function(e) {
        const { lat, lng } = e.latlng;
        editMarker.setLatLng([lat, lng]);
        setEditCoordinates(lat, lng);
        getEditAddress(lat, lng);
    });
}

// Save coordinates to hidden inputs for edit form
function setEditCoordinates(lat, lng) {
    const latInput = document.getElementById('edit_latitude');
    const lngInput = document.getElementById('edit_longitude');
    if (latInput) latInput.value = lat;
    if (lngInput) lngInput.value = lng;
}

// Reverse geocode to get address for edit form
function getEditAddress(lat, lng) {
    fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
        .then(res => res.json())
        .then(data => {
            const address = data.display_name || 'Unknown location';
            const addressInput = document.getElementById('edit_address');
            if (addressInput) {
                addressInput.value = address;
            }
        })
        .catch(() => {
            const addressInput = document.getElementById('edit_address');
            if (addressInput) {
                addressInput.value = 'Unable to fetch address';
            }
        });
}
    @endif
@endif

// Delete Report Function
async function deleteReport(reportId) {
    const result = await Swal.fire({
        title: 'Are you sure?',
        text: "Do you want to delete this report? This action cannot be undone!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        buttonsStyling: false,
        customClass: {
            confirmButton: 'btn btn-danger',
            cancelButton: 'btn btn-secondary'
        }
    });
    
    if (!result.isConfirmed) return;
    
    try {
        const response = await fetch(`/reports/${reportId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        });
        
        const contentType = response.headers.get('content-type');
        let data;
        
        if (contentType && contentType.includes('application/json')) {
            data = await response.json();
        } else {
            const text = await response.text();
            try {
                data = JSON.parse(text);
            } catch (e) {
                throw new Error('Invalid response from server');
            }
        }
        
        if (response.ok && data.status === 'success') {
            Swal.fire({
                icon: 'success',
                title: 'Deleted!',
                text: data.message || 'Report has been deleted.',
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                // Redirect to reports history page
                window.location.href = '{{ route("reports.history") }}';
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Cannot Delete',
                text: data.message || 'Failed to delete report.',
                confirmButtonText: 'OK'
            });
        }
    } catch (error) {
        console.error('Delete error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'An error occurred while deleting the report: ' + error.message,
            confirmButtonText: 'OK'
        });
    }
}

// Cancel Report Function
async function cancelReport(reportId) {
    const result = await Swal.fire({
        title: 'Cancel Report?',
        html: "Are you sure you want to cancel this report? All responder assignments will be cancelled.<br><br><strong>Once you cancel this, it cannot be undone.</strong><br><br>If you wish to submit a new report, please create a new one after cancelling this.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, cancel it!',
        cancelButtonText: 'No, keep it',
        confirmButtonColor: '#ffc107',
        cancelButtonColor: '#6c757d',
        buttonsStyling: false,
        customClass: {
            confirmButton: 'btn btn-warning',
            cancelButton: 'btn btn-secondary'
        }
    });
    
    if (!result.isConfirmed) return;
    
    try {
        const response = await fetch(`/reports/${reportId}/cancel`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        });
        
        const contentType = response.headers.get('content-type');
        let data;
        
        if (contentType && contentType.includes('application/json')) {
            data = await response.json();
        } else {
            const text = await response.text();
            try {
                data = JSON.parse(text);
            } catch (e) {
                throw new Error('Invalid response from server');
            }
        }
        
        if (response.ok && data.status === 'success') {
            Swal.fire({
                icon: 'success',
                title: 'Cancelled!',
                text: data.message || 'Report has been cancelled successfully.',
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                // Reload the page to show updated status
                window.location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Cannot Cancel',
                text: data.message || 'Failed to cancel report.',
                confirmButtonText: 'OK'
            });
        }
    } catch (error) {
        console.error('Cancel error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'An error occurred while cancelling the report: ' + error.message,
            confirmButtonText: 'OK'
        });
    }
}

</script>
@endsection

