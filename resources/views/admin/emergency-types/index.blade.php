@extends('layouts.app')

@section('title', 'Emergency Types Management')

@section('content')
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12 d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-0"><i class="bi bi-exclamation-triangle me-2"></i>Emergency Types Management</h2>
                    <p class="text-muted">Manage emergency type categories</p>
                </div>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal"
                    data-bs-target="#createEmergencyTypeModal">
                    <i class="bi bi-plus-circle me-2"></i>Create Type
                </button>
            </div>
        </div>

        <!-- Emergency Types Table -->
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                @if($types->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Name</th>
                                    <th>Icon</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($types as $type)
                                    <tr>
                                        <td><code>{{ $type->code }}</code></td>
                                        <td>{{ $type->name }}</td>
                                        <td><i class="{{ $type->icon }}"></i> {{ $type->icon }}</td>
                                        <td>{{ Str::limit($type->description ?? 'N/A', 50) }}</td>
                                        <td>
                                            @if($type->status)
                                                <span class="badge bg-success">Active</span>
                                            @else
                                                <span class="badge bg-secondary">Inactive</span>
                                            @endif
                                        </td>
                                        <td>{{ $type->created_at ? $type->created_at->format('M d, Y') : 'N/A' }}</td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-primary edit-type-btn"
                                                data-bs-toggle="modal" data-bs-target="#editEmergencyTypeModal"
                                                data-type-id="{{ $type->id }}" data-type-code="{{ $type->code }}"
                                                data-type-name="{{ $type->name }}" data-type-icon="{{ $type->icon }}"
                                                data-type-description="{{ $type->description }}"
                                                data-type-status="{{ $type->status }}">
                                                <i class="bi bi-pencil"></i> <span class="d-none d-md-inline">Edit</span>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger delete-type-btn"
                                                data-type-id="{{ $type->id }}" data-type-name="{{ $type->name }}">
                                                <i class="bi bi-trash"></i> <span class="d-none d-md-inline">Delete</span>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div>
                        {{ $types->appends(request()->query())->links() }}
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="bi bi-inbox fs-1 text-muted"></i>
                        <p class="text-muted mt-3">No emergency types found</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Create Emergency Type Modal -->
    <div class="modal fade" id="createEmergencyTypeModal" tabindex="-1" aria-labelledby="createEmergencyTypeModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createEmergencyTypeModalLabel">
                        <i class="bi bi-plus-circle me-2"></i>Create Emergency Type
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="createEmergencyTypeForm">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="modal_code" class="form-label">Code</label>
                            <input type="text" class="form-control form-control-sm" id="modal_code" name="code"
                                value="{{ old('code') }}" required placeholder="e.g., fire, medical_emergency">
                            <small class="form-text text-muted">Unique identifier (lowercase, underscores allowed)</small>
                            <div class="invalid-feedback" id="modal_code_error"></div>
                        </div>

                        <div class="mb-3">
                            <label for="modal_name" class="form-label">Name</label>
                            <input type="text" class="form-control form-control-sm" id="modal_name" name="name"
                                value="{{ old('name') }}" required>
                            <div class="invalid-feedback" id="modal_name_error"></div>
                        </div>

                        <div class="mb-3">
                            <label for="modal_icon" class="form-label">Icon (Bootstrap Icons class)</label>
                            <input type="text" class="form-control form-control-sm" id="modal_icon" name="icon"
                                value="{{ old('icon') }}" placeholder="e.g., bi bi-fire">
                            <small class="form-text text-muted">Bootstrap Icons class name</small>
                            <div class="invalid-feedback" id="modal_icon_error"></div>
                        </div>

                        <div class="mb-3">
                            <label for="modal_description" class="form-label">Description</label>
                            <textarea class="form-control form-control-sm" id="modal_description" name="description"
                                rows="3">{{ old('description') }}</textarea>
                            <div class="invalid-feedback" id="modal_description_error"></div>
                        </div>

                        <div class="mb-3">
                            <label for="modal_status" class="form-label">Status</label>
                            <select class="form-select form-select-sm" id="modal_status" name="status" required>
                                <option value="1" {{ old('status', '1') === '1' ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ old('status') === '0' ? 'selected' : '' }}>Inactive</option>
                            </select>
                            <div class="invalid-feedback" id="modal_status_error"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary btn-sm"
                            data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm" id="createTypeSubmitBtn">
                            <i class="bi bi-check-circle me-2"></i>Create Type
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Emergency Type Modal -->
    <div class="modal fade" id="editEmergencyTypeModal" tabindex="-1" aria-labelledby="editEmergencyTypeModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editEmergencyTypeModalLabel">
                        <i class="bi bi-pencil me-2"></i>Edit Emergency Type
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editEmergencyTypeForm">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" id="edit_type_id" name="type_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_code" class="form-label">Code</label>
                            <input type="text" class="form-control form-control-sm" id="edit_code" name="code" required
                                placeholder="e.g., fire, medical_emergency">
                            <small class="form-text text-muted">Unique identifier (lowercase, underscores allowed)</small>
                            <div class="invalid-feedback" id="edit_code_error"></div>
                        </div>

                        <div class="mb-3">
                            <label for="edit_name" class="form-label">Name</label>
                            <input type="text" class="form-control form-control-sm" id="edit_name" name="name" required>
                            <div class="invalid-feedback" id="edit_name_error"></div>
                        </div>

                        <div class="mb-3">
                            <label for="edit_icon" class="form-label">Icon (Bootstrap Icons class)</label>
                            <input type="text" class="form-control form-control-sm" id="edit_icon" name="icon"
                                placeholder="e.g., bi bi-fire">
                            <small class="form-text text-muted">Bootstrap Icons class name</small>
                            <div class="invalid-feedback" id="edit_icon_error"></div>
                        </div>

                        <div class="mb-3">
                            <label for="edit_description" class="form-label">Description</label>
                            <textarea class="form-control form-control-sm" id="edit_description" name="description"
                                rows="3"></textarea>
                            <div class="invalid-feedback" id="edit_description_error"></div>
                        </div>

                        <div class="mb-3">
                            <label for="edit_status" class="form-label">Status</label>
                            <select class="form-select form-select-sm" id="edit_status" name="status" required>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                            <div class="invalid-feedback" id="edit_status_error"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary btn-sm"
                            data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm" id="editTypeSubmitBtn">
                            <i class="bi bi-check-circle me-2"></i>Update Type
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @if($errors->any() && old('_token'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var createEmergencyTypeModal = new bootstrap.Modal(document.getElementById('createEmergencyTypeModal'));
                createEmergencyTypeModal.show();
            });
        </script>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Handle create emergency type form submission
            const createTypeForm = document.getElementById('createEmergencyTypeForm');
            const createTypeSubmitBtn = document.getElementById('createTypeSubmitBtn');
            const createTypeModalElement = document.getElementById('createEmergencyTypeModal');

            if (createTypeForm) {
                createTypeForm.addEventListener('submit', function (e) {
                    e.preventDefault();

                    // Clear previous errors
                    document.querySelectorAll('#createEmergencyTypeForm .is-invalid').forEach(el => {
                        el.classList.remove('is-invalid');
                    });
                    document.querySelectorAll('#createEmergencyTypeForm .invalid-feedback').forEach(el => {
                        el.textContent = '';
                    });

                    // Disable submit button
                    createTypeSubmitBtn.disabled = true;
                    createTypeSubmitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Creating...';

                    const formData = new FormData(createTypeForm);

                    fetch('{{ route('admin.emergency-types.store') }}', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        }
                    })
                        .then(response => {
                            if (!response.ok && response.status === 422) {
                                return response.json().then(data => {
                                    throw { validation: true, data: data };
                                });
                            }
                            return response.json();
                        })
                        .then(data => {
                            if (data.success) {
                                const createTypeModal = bootstrap.Modal.getInstance(createTypeModalElement);
                                if (createTypeModal) {
                                    createTypeModal.hide();
                                }
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success!',
                                    text: data.message || 'Emergency type created successfully.',
                                    showConfirmButton: true,
                                    confirmButtonText: 'OK',
                                    buttonsStyling: false,
                                    customClass: {
                                        confirmButton: 'btn btn-success'
                                    },
                                    allowOutsideClick: false
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        location.reload();
                                    }
                                });
                            } else {
                                if (data.errors) {
                                    Object.keys(data.errors).forEach(field => {
                                        const input = document.getElementById('modal_' + field);
                                        const errorDiv = document.getElementById('modal_' + field + '_error');
                                        if (input) {
                                            input.classList.add('is-invalid');
                                        }
                                        if (errorDiv) {
                                            errorDiv.textContent = data.errors[field][0];
                                        }
                                    });
                                }

                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error!',
                                    text: data.message || 'Failed to create emergency type. Please check the form for errors.',
                                    buttonsStyling: false,
                                    customClass: {
                                        confirmButton: 'btn btn-danger'
                                    }
                                });

                                createTypeSubmitBtn.disabled = false;
                                createTypeSubmitBtn.innerHTML = '<i class="bi bi-check-circle me-2"></i>Create Type';
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);

                            if (error.validation && error.data) {
                                if (error.data.errors) {
                                    Object.keys(error.data.errors).forEach(field => {
                                        const input = document.getElementById('modal_' + field);
                                        const errorDiv = document.getElementById('modal_' + field + '_error');
                                        if (input) {
                                            input.classList.add('is-invalid');
                                        }
                                        if (errorDiv) {
                                            errorDiv.textContent = error.data.errors[field][0];
                                        }
                                    });
                                }

                                Swal.fire({
                                    icon: 'error',
                                    title: 'Validation Error!',
                                    text: error.data.message || 'Please check the form for errors.'
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error!',
                                    text: error.message || 'An unexpected error occurred. Please try again.'
                                });
                            }

                            createTypeSubmitBtn.disabled = false;
                            createTypeSubmitBtn.innerHTML = '<i class="bi bi-check-circle me-2"></i>Create Type';
                        });
                });
            }

            // Handle edit emergency type button click
            document.querySelectorAll('.edit-type-btn').forEach(button => {
                button.addEventListener('click', function () {
                    const typeId = this.getAttribute('data-type-id');
                    const typeCode = this.getAttribute('data-type-code');
                    const typeName = this.getAttribute('data-type-name');
                    const typeIcon = this.getAttribute('data-type-icon');
                    const typeDescription = this.getAttribute('data-type-description');
                    const typeStatus = this.getAttribute('data-type-status');

                    // Populate form fields
                    document.getElementById('edit_type_id').value = typeId;
                    document.getElementById('edit_code').value = typeCode;
                    document.getElementById('edit_name').value = typeName;
                    document.getElementById('edit_icon').value = typeIcon || '';
                    document.getElementById('edit_description').value = typeDescription || '';
                    document.getElementById('edit_status').value = typeStatus;

                    // Clear previous errors
                    document.querySelectorAll('#editEmergencyTypeForm .is-invalid').forEach(el => {
                        el.classList.remove('is-invalid');
                    });
                    document.querySelectorAll('#editEmergencyTypeForm .invalid-feedback').forEach(el => {
                        el.textContent = '';
                    });
                });
            });

            // Handle edit form submission
            const editTypeForm = document.getElementById('editEmergencyTypeForm');
            const editTypeSubmitBtn = document.getElementById('editTypeSubmitBtn');

            if (editTypeForm) {
                editTypeForm.addEventListener('submit', function (e) {
                    e.preventDefault();

                    const typeId = document.getElementById('edit_type_id').value;
                    const formData = new FormData(editTypeForm);

                    // Clear previous errors
                    document.querySelectorAll('#editEmergencyTypeForm .is-invalid').forEach(el => {
                        el.classList.remove('is-invalid');
                    });
                    document.querySelectorAll('#editEmergencyTypeForm .invalid-feedback').forEach(el => {
                        el.textContent = '';
                    });

                    // Disable submit button
                    editTypeSubmitBtn.disabled = true;
                    editTypeSubmitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Updating...';

                    fetch(`{{ url('/admin/emergency-types') }}/${typeId}`, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        }
                    })
                        .then(response => {
                            if (!response.ok && response.status === 422) {
                                return response.json().then(data => {
                                    throw { validation: true, data: data };
                                });
                            }
                            return response.json();
                        })
                        .then(data => {
                            if (data.success) {
                                const editTypeModalElement = document.getElementById('editEmergencyTypeModal');
                                const editTypeModal = bootstrap.Modal.getInstance(editTypeModalElement);
                                if (editTypeModal) {
                                    editTypeModal.hide();
                                }
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success!',
                                    text: data.message || 'Emergency type updated successfully.',
                                    buttonsStyling: false,
                                    customClass: {
                                        confirmButton: 'btn btn-success'
                                    },
                                    showConfirmButton: true,
                                    confirmButtonText: 'OK',
                                    allowOutsideClick: false
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        location.reload();
                                    }
                                });
                            } else {
                                if (data.errors) {
                                    Object.keys(data.errors).forEach(field => {
                                        const input = document.getElementById('edit_' + field);
                                        const errorDiv = document.getElementById('edit_' + field + '_error');
                                        if (input) {
                                            input.classList.add('is-invalid');
                                        }
                                        if (errorDiv) {
                                            errorDiv.textContent = data.errors[field][0];
                                        }
                                    });
                                }

                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error!',
                                    text: data.message || 'Failed to update emergency type. Please check the form for errors.',
                                    buttonsStyling: false,
                                    customClass: {
                                        confirmButton: 'btn btn-danger'
                                    }
                                });

                                editTypeSubmitBtn.disabled = false;
                                editTypeSubmitBtn.innerHTML = '<i class="bi bi-check-circle me-2"></i>Update Type';
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);

                            if (error.validation && error.data) {
                                if (error.data.errors) {
                                    Object.keys(error.data.errors).forEach(field => {
                                        const input = document.getElementById('edit_' + field);
                                        const errorDiv = document.getElementById('edit_' + field + '_error');
                                        if (input) {
                                            input.classList.add('is-invalid');
                                        }
                                        if (errorDiv) {
                                            errorDiv.textContent = error.data.errors[field][0];
                                        }
                                    });
                                }

                                Swal.fire({
                                    icon: 'error',
                                    title: 'Validation Error!',
                                    text: error.data.message || 'Please check the form for errors.',
                                    buttonsStyling: false,
                                    customClass: {
                                        confirmButton: 'btn btn-danger'
                                    }
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error!',
                                    text: error.message || 'An unexpected error occurred. Please try again.',
                                    buttonsStyling: false,
                                    customClass: {
                                        confirmButton: 'btn btn-danger'
                                    }
                                });
                            }

                            editTypeSubmitBtn.disabled = false;
                            editTypeSubmitBtn.innerHTML = '<i class="bi bi-check-circle me-2"></i>Update Type';
                        });
                });
            }

            // Delete emergency type with SweetAlert confirmation
            document.querySelectorAll('.delete-type-btn').forEach(button => {
                button.addEventListener('click', function () {
                    const typeId = this.getAttribute('data-type-id');
                    const typeName = this.getAttribute('data-type-name');

                    Swal.fire({
                        title: 'Are you sure?',
                        text: `Do you want to delete emergency type "${typeName}"? This action cannot be undone!`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, delete it!',
                        cancelButtonText: 'Cancel',
                        buttonsStyling: false,
                        customClass: {
                            confirmButton: 'btn btn-danger',
                            cancelButton: 'btn btn-secondary'
                        },
                        showLoaderOnConfirm: true,
                        preConfirm: () => {
                            return fetch(`{{ url('/admin/emergency-types') }}/${typeId}`, {
                                method: 'DELETE',
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json'
                                }
                            })
                                .then(response => {
                                    if (!response.ok) {
                                        return response.json().then(data => {
                                            throw new Error(data.message || 'Failed to delete emergency type');
                                        });
                                    }
                                    return response.json();
                                })
                                .then(data => {
                                    if (!data.success) {
                                        throw new Error(data.message || 'Failed to delete emergency type');
                                    }
                                    return data;
                                })
                                .catch(error => {
                                    Swal.showValidationMessage(`Request failed: ${error.message}`);
                                });
                        },
                        allowOutsideClick: () => !Swal.isLoading()
                    }).then((result) => {
                        if (result.isConfirmed) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted!',
                                text: result.value.message || 'Emergency type has been deleted.',
                                showConfirmButton: true,
                                confirmButtonText: 'OK',
                                buttonsStyling: false,
                                customClass: {
                                    confirmButton: 'btn btn-success'
                                },
                                allowOutsideClick: false
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    location.reload();
                                }
                            });
                        }
                    });
                });
            });
        });
    </script>
@endsection