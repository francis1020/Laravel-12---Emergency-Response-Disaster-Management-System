@extends('layouts.app')

@section('title', 'Resource Management')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-0"><i class="bi bi-truck me-2"></i>Resource Management</h2>
                    <p class="text-muted">Manage vehicles, equipment, personnel, and facilities</p>
                </div>
                @if(Auth::user()->isAdmin() || Auth::user()->isResponder())
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createResourceModal">
                    <i class="bi bi-plus-circle me-2"></i>Add Resource
                </button>
                @endif
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('resources.index') }}" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-select">
                        <option value="">All Types</option>
                        <option value="vehicle" {{ request('type') == 'vehicle' ? 'selected' : '' }}>Vehicle</option>
                        <option value="equipment" {{ request('type') == 'equipment' ? 'selected' : '' }}>Equipment</option>
                        <option value="personnel" {{ request('type') == 'personnel' ? 'selected' : '' }}>Personnel</option>
                        <option value="facility" {{ request('type') == 'facility' ? 'selected' : '' }}>Facility</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="available" {{ request('status') == 'available' ? 'selected' : '' }}>Available</option>
                        <option value="in_use" {{ request('status') == 'in_use' ? 'selected' : '' }}>In Use</option>
                        <option value="maintenance" {{ request('status') == 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                        <option value="unavailable" {{ request('status') == 'unavailable' ? 'selected' : '' }}>Unavailable</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" placeholder="Search by name, identifier..." value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="bi bi-search me-2"></i>Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Resources Grid -->
    <div class="row g-3" id="resourcesGrid">
        @php
            $resourcesData = $resources->items();
        @endphp
        @foreach($resources as $resource)
        <div class="col-md-4" data-resource-id="{{ $resource->id }}" data-resource-data="{{ json_encode($resource) }}">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h5 class="mb-1">{{ $resource->name }}</h5>
                            <p class="text-muted mb-0 small">
                                <i class="bi bi-tag me-1"></i>{{ ucfirst($resource->type) }}
                                @if($resource->identifier)
                                    | {{ $resource->identifier }}
                                @endif
                            </p>
                            @if($resource->responder)
                                <p class="text-muted mb-0 small">
                                    <i class="bi bi-person me-1"></i>Owner: {{ $resource->responder->name }}
                                </p>
                            @endif
                        </div>
                        <span class="badge bg-{{ $resource->status == 'available' ? 'success' : ($resource->status == 'in_use' ? 'warning' : ($resource->status == 'maintenance' ? 'info' : 'secondary')) }}">
                            {{ ucfirst(str_replace('_', ' ', $resource->status)) }}
                        </span>
                    </div>
                    
                    @if($resource->description)
                    <p class="text-muted small mb-3">{{ Str::limit($resource->description, 100) }}</p>
                    @endif

                    <div class="mb-3">
                        @if($resource->assignedUser)
                        <p class="mb-1 small">
                            <strong>Assigned to:</strong> {{ $resource->assignedUser->name }}
                        </p>
                        @endif
                        @if($resource->currentReport)
                        <p class="mb-0 small">
                            <strong>Report:</strong> 
                            <a href="{{ route('reports.show', $resource->current_report_id) }}">#{{ $resource->current_report_id }}</a>
                        </p>
                        @endif
                    </div>

                    <div class="btn-group w-100" role="group">
                        @if($resource->status == 'available' && (Auth::user()->isAdmin() || (Auth::user()->isResponder() && $resource->responder_id == Auth::id())))
                        <button class="btn btn-sm btn-success" onclick="assignResource({{ $resource->id }})">
                            <i class="bi bi-check-circle me-1"></i>Assign
                        </button>
                        @endif
                        @if($resource->status == 'in_use' && (Auth::user()->isAdmin() || $resource->assigned_to == Auth::id()))
                        <button class="btn btn-sm btn-warning" onclick="releaseResource({{ $resource->id }})">
                            <i class="bi bi-x-circle me-1"></i>Release
                        </button>
                        @endif
                        @if(Auth::user()->isAdmin() || (Auth::user()->isResponder() && $resource->responder_id == Auth::id()))
                        <button class="btn btn-sm btn-primary" onclick="editResource({{ $resource->id }})">
                            <i class="bi bi-pencil me-1"></i>Edit
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="deleteResource({{ $resource->id }})">
                            <i class="bi bi-trash me-1"></i>Delete
                        </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endforeach
        
        <!-- No Resources Message -->
        <div class="col-12" id="noResourcesMessage" style="{{ count($resources) > 0 ? 'display: none;' : '' }}">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-5">
                    <i class="bi bi-inbox fs-1 text-muted"></i>
                    <p class="text-muted mt-3">No resources found</p>
                    @if(Auth::user()->isAdmin() || Auth::user()->isResponder())
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createResourceModal">
                        <i class="bi bi-plus-circle me-2"></i>Add First Resource
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Pagination -->
    <div class="mt-4">
        {{ $resources->links() }}
    </div>
</div>

<!-- Create Resource Modal -->
@if(Auth::user()->isAdmin() || Auth::user()->isResponder())
<div class="modal fade" id="createResourceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Resource</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="createResourceForm">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name *</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Type *</label>
                        <select name="type" class="form-select" required>
                            <option value="vehicle">Vehicle</option>
                            <option value="equipment">Equipment</option>
                            <option value="personnel">Personnel</option>
                            <option value="facility">Facility</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Identifier</label>
                        <input type="text" name="identifier" class="form-control" placeholder="License plate, serial number, etc.">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="available">Available</option>
                            <option value="maintenance">Maintenance</option>
                            <option value="unavailable">Unavailable</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Resource</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Resource Modal -->
<div class="modal fade" id="editResourceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Resource</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editResourceForm">
                @csrf
                <input type="hidden" name="resource_id" id="editResourceId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name *</label>
                        <input type="text" name="name" id="editResourceName" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Type *</label>
                        <select name="type" id="editResourceType" class="form-select" required>
                            <option value="vehicle">Vehicle</option>
                            <option value="equipment">Equipment</option>
                            <option value="personnel">Personnel</option>
                            <option value="facility">Facility</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Identifier</label>
                        <input type="text" name="identifier" id="editResourceIdentifier" class="form-control" placeholder="License plate, serial number, etc.">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="editResourceDescription" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" id="editResourceStatus" class="form-select">
                            <option value="available">Available</option>
                            <option value="in_use">In Use</option>
                            <option value="maintenance">Maintenance</option>
                            <option value="unavailable">Unavailable</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Resource</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<script>
// Helper function to create resource card HTML
function createResourceCard(resource) {
    const statusColors = {
        'available': 'success',
        'in_use': 'warning',
        'maintenance': 'info',
        'unavailable': 'secondary'
    };
    const statusColor = statusColors[resource.status] || 'secondary';
    const statusBadge = `<span class="badge bg-${statusColor}">${resource.status.charAt(0).toUpperCase() + resource.status.slice(1).replace('_', ' ')}</span>`;
    
    const ownerInfo = resource.responder ? `<p class="text-muted mb-0 small"><i class="bi bi-person me-1"></i>Owner: ${resource.responder.name}</p>` : '';
    const description = resource.description ? `<p class="text-muted small mb-3">${resource.description.substring(0, 100)}${resource.description.length > 100 ? '...' : ''}</p>` : '';
    const assignedInfo = resource.assigned_user ? `<p class="mb-1 small"><strong>Assigned to:</strong> ${resource.assigned_user.name}</p>` : '';
    const reportInfo = resource.current_report_id ? `<p class="mb-0 small"><strong>Report:</strong> <a href="/reports/${resource.current_report_id}">#${resource.current_report_id}</a></p>` : '';
    
    const canAssign = resource.status === 'available' && ({{ Auth::user()->isAdmin() ? 'true' : 'false' }} || ({{ Auth::user()->isResponder() ? 'true' : 'false' }} && resource.responder_id == {{ Auth::id() }}));
    const canRelease = resource.status === 'in_use' && ({{ Auth::user()->isAdmin() ? 'true' : 'false' }} || resource.assigned_to == {{ Auth::id() }});
    const canEdit = {{ Auth::user()->isAdmin() ? 'true' : 'false' }} || ({{ Auth::user()->isResponder() ? 'true' : 'false' }} && resource.responder_id == {{ Auth::id() }});
    
    const assignBtn = canAssign ? `<button class="btn btn-sm btn-success" onclick="assignResource(${resource.id})"><i class="bi bi-check-circle me-1"></i>Assign</button>` : '';
    const releaseBtn = canRelease ? `<button class="btn btn-sm btn-warning" onclick="releaseResource(${resource.id})"><i class="bi bi-x-circle me-1"></i>Release</button>` : '';
    const editBtn = canEdit ? `<button class="btn btn-sm btn-primary" onclick="editResource(${resource.id})"><i class="bi bi-pencil me-1"></i>Edit</button>` : '';
    const deleteBtn = canEdit ? `<button class="btn btn-sm btn-danger" onclick="deleteResource(${resource.id})"><i class="bi bi-trash me-1"></i>Delete</button>` : '';
    
    return `
        <div class="col-md-4" data-resource-id="${resource.id}">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h5 class="mb-1">${resource.name}</h5>
                            <p class="text-muted mb-0 small">
                                <i class="bi bi-tag me-1"></i>${resource.type.charAt(0).toUpperCase() + resource.type.slice(1)}${resource.identifier ? ' | ' + resource.identifier : ''}
                            </p>
                            ${ownerInfo}
                        </div>
                        ${statusBadge}
                    </div>
                    ${description}
                    <div class="mb-3">
                        ${assignedInfo}
                        ${reportInfo}
                    </div>
                    <div class="btn-group w-100" role="group">
                        ${assignBtn}
                        ${releaseBtn}
                        ${editBtn}
                        ${deleteBtn}
                    </div>
                </div>
            </div>
        </div>
    `;
}

// Helper function to update resource card
function updateResourceCard(resource) {
    const cardElement = document.querySelector(`[data-resource-id="${resource.id}"]`);
    if (cardElement) {
        cardElement.outerHTML = createResourceCard(resource);
        // Store resource data in the new card element
        const newCardElement = document.querySelector(`[data-resource-id="${resource.id}"]`);
        if (newCardElement) {
            newCardElement.setAttribute('data-resource-data', JSON.stringify(resource));
        }
    }
}

// Helper function to remove resource card
function removeResourceCard(resourceId) {
    const cardElement = document.querySelector(`[data-resource-id="${resourceId}"]`);
    if (cardElement) {
        cardElement.remove();
    }
    // Check if we need to show "No resources found" message
    updateNoResourcesMessage();
}

// Helper function to update "No resources found" message visibility
function updateNoResourcesMessage() {
    const resourcesGrid = document.getElementById('resourcesGrid');
    const noResourcesMessage = document.getElementById('noResourcesMessage');
    
    if (!resourcesGrid || !noResourcesMessage) return;
    
    // Count resource cards (excluding the "no resources" message itself)
    const resourceCards = resourcesGrid.querySelectorAll('[data-resource-id]');
    const hasResources = resourceCards.length > 0;
    
    if (hasResources) {
        noResourcesMessage.style.display = 'none';
    } else {
        noResourcesMessage.style.display = 'block';
    }
}

// Create Resource
document.getElementById('createResourceForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    
    submitBtn.disabled = true;
    submitBtn.textContent = 'Creating...';
    
    try {
        const response = await fetch('{{ route("resources.store") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: formData
        });
        
        const data = await response.json();
        if (data.status === 'success') {
            // Close modal
            bootstrap.Modal.getInstance(document.getElementById('createResourceModal')).hide();
            
            // Reset form
            this.reset();
            
            // Add new resource card to the grid
            const resourcesGrid = document.getElementById('resourcesGrid');
            if (resourcesGrid) {
                const newCard = createResourceCard(data.resource);
                resourcesGrid.insertAdjacentHTML('beforeend', newCard);
                
                // Store resource data in a data attribute for easy access
                const newCardElement = resourcesGrid.querySelector(`[data-resource-id="${data.resource.id}"]`);
                if (newCardElement) {
                    newCardElement.setAttribute('data-resource-data', JSON.stringify(data.resource));
                }
                
                // Hide "No resources found" message if it exists
                updateNoResourcesMessage();
            }
            
            // Show success message
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: 'Resource created successfully',
                timer: 2000,
                showConfirmButton: false
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message || 'Failed to create resource'
            });
        }
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'An error occurred while creating the resource'
        });
    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    }
});

// Assign Resource
async function assignResource(resourceId) {
    // First, get available reports for this responder
    let assignedReports = [];
    @if(Auth::user()->isResponder() && !Auth::user()->isAdmin())
    try {
        const reportsResponse = await fetch('/resources/available?get_reports=1', {
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        });
        const reportsData = await reportsResponse.json();
        if (reportsData.status === 'success' && reportsData.assignedReports) {
            assignedReports = reportsData.assignedReports;
        }
    } catch (error) {
        console.error('Error fetching assigned reports:', error);
    }
    @endif

    let reportId;
    
    @if(Auth::user()->isResponder() && !Auth::user()->isAdmin())
    if (assignedReports.length > 0) {
        // Show dropdown with assigned reports
        const reportOptions = assignedReports.map(r => ({
            value: r.id,
            text: `Report #${r.id} - ${r.type.charAt(0).toUpperCase() + r.type.slice(1).replace('_', ' ')} (${r.address ? r.address.substring(0, 30) + '...' : 'Unknown'})`
        })).reduce((acc, opt) => {
            acc[opt.value] = opt.text;
            return acc;
        }, {});

        const { value: selectedReportId } = await Swal.fire({
            title: 'Assign Resource',
            input: 'select',
            inputLabel: 'Select Report',
            inputOptions: reportOptions,
            showCancelButton: true,
            confirmButtonText: 'Assign',
            cancelButtonText: 'Cancel',
            inputValidator: (value) => {
                if (!value) {
                    return 'Please select a report';
                }
            }
        });
        
        reportId = selectedReportId;
    } else {
        // No assigned reports, show message
        Swal.fire({
            icon: 'info',
            title: 'No Active Reports',
            text: 'You need to be assigned to a report before you can assign resources.',
            confirmButtonText: 'OK'
        });
        return;
    }
    @else
    // For admins, use number input
    const { value: adminReportId } = await Swal.fire({
        title: 'Assign Resource',
        input: 'number',
        inputLabel: 'Enter Report ID',
        inputPlaceholder: 'Report ID',
        showCancelButton: true,
        confirmButtonText: 'Assign',
        cancelButtonText: 'Cancel',
        inputValidator: (value) => {
            if (!value) {
                return 'Please enter a report ID';
            }
        }
    });
    reportId = adminReportId;
    @endif
    
    if (!reportId) return;
    
    Swal.fire({
        title: 'Assigning...',
        text: 'Please wait',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    try {
        const response = await fetch(`/resources/${resourceId}/assign`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ report_id: reportId })
        });
        
        const data = await response.json();
        if (data.status === 'success' && data.resource) {
            // Update the resource card with new data
            updateResourceCard(data.resource);
            
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: 'Resource assigned successfully',
                timer: 2000,
                showConfirmButton: false
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message || 'Failed to assign resource'
            });
        }
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'An error occurred while assigning the resource'
        });
    }
}

// Release Resource
async function releaseResource(resourceId) {
    const result = await Swal.fire({
        title: 'Release Resource?',
        text: 'Are you sure you want to release this resource?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, Release',
        cancelButtonText: 'Cancel',
        buttonsStyling: false,
        customClass: {
            confirmButton: 'btn btn-danger',
            cancelButton: 'btn btn-secondary'
        }
    });
    
    if (!result.isConfirmed) return;
    
    Swal.fire({
        title: 'Releasing...',
        text: 'Please wait',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    try {
        const response = await fetch(`/resources/${resourceId}/release`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });
        
        const data = await response.json();
        if (data.status === 'success' && data.resource) {
            // Update the resource card with new data
            updateResourceCard(data.resource);
            
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: 'Resource released successfully',
                timer: 2000,
                showConfirmButton: false
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message || 'Failed to release resource'
            });
        }
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'An error occurred while releasing the resource'
        });
    }
}

// Edit Resource
async function editResource(resourceId) {
    let resource = null;
    
    // First, try to get resource data from the DOM card's data attribute
    const cardElement = document.querySelector(`[data-resource-id="${resourceId}"]`);
    if (cardElement) {
        const resourceDataAttr = cardElement.getAttribute('data-resource-data');
        if (resourceDataAttr) {
            try {
                resource = JSON.parse(resourceDataAttr);
            } catch (e) {
                console.error('Error parsing resource data:', e);
            }
        }
    }
    
    // If not found in DOM data attribute, try to get from resources list
    if (!resource) {
        const resources = @json($resourcesData);
        resource = resources.find(r => r.id == resourceId);
    }
    
    // If still not found, fetch from server
    if (!resource) {
        try {
            Swal.fire({
                title: 'Loading...',
                text: 'Please wait',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            const response = await fetch(`/resources/${resourceId}`, {
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            });
            
            if (response.ok) {
                const data = await response.json();
                resource = data.resource || data;
                
                // Store in DOM for future use
                if (cardElement && resource) {
                    cardElement.setAttribute('data-resource-data', JSON.stringify(resource));
                }
            } else {
                throw new Error('Resource not found');
            }
            
            Swal.close();
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Resource not found. Please refresh the page.'
            });
            return;
        }
    }
    
    if (!resource) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Resource not found'
        });
        return;
    }
    
    // Populate form with resource data
    document.getElementById('editResourceId').value = resource.id;
    document.getElementById('editResourceName').value = resource.name || '';
    document.getElementById('editResourceType').value = resource.type || 'equipment';
    document.getElementById('editResourceIdentifier').value = resource.identifier || '';
    document.getElementById('editResourceDescription').value = resource.description || '';
    document.getElementById('editResourceStatus').value = resource.status || 'available';
    
    // Show modal
    const editModal = new bootstrap.Modal(document.getElementById('editResourceModal'));
    editModal.show();
}

// Update Resource Form Submit
document.getElementById('editResourceForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const resourceId = formData.get('resource_id');
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    
    submitBtn.disabled = true;
    submitBtn.textContent = 'Updating...';
    
    // Convert FormData to object for PATCH request
    const data = {};
    formData.forEach((value, key) => {
        if (key !== 'resource_id' && key !== '_token') {
            data[key] = value;
        }
    });
    
    try {
        const response = await fetch(`/resources/${resourceId}`, {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        if (result.status === 'success' && result.resource) {
            // Close modal
            bootstrap.Modal.getInstance(document.getElementById('editResourceModal')).hide();
            
            // Update the resource card with new data
            updateResourceCard(result.resource);
            
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: 'Resource updated successfully',
                timer: 2000,
                showConfirmButton: false
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: result.message || 'Failed to update resource'
            });
        }
    } catch (error) {
        console.error('Error updating resource:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'An error occurred while updating the resource'
        });
    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    }
});

// Delete Resource
async function deleteResource(resourceId) {
    const result = await Swal.fire({
        title: 'Delete Resource?',
        text: 'Are you sure you want to delete this resource? This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, Delete',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#dc3545',
        buttonsStyling: false,
        customClass: {
            confirmButton: 'btn btn-danger',
            cancelButton: 'btn btn-secondary'
        }
    });
    
    if (!result.isConfirmed) return;
    
    try {
        const response = await fetch(`/resources/${resourceId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });
        
        const data = await response.json();
        if (data.status === 'success') {
            // Remove the resource card from the DOM
            removeResourceCard(resourceId);
            
            Swal.fire({
                icon: 'success',
                title: 'Deleted',
                text: 'Resource deleted successfully',
                timer: 2000,
                showConfirmButton: false
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message || 'Failed to delete resource'
            });
        }
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'An error occurred while deleting the resource'
        });
    }
}
</script>
@endsection

