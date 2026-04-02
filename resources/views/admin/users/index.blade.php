@extends('layouts.app')

@section('title', 'User Management')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <h2 class="mb-0"><i class="bi bi-people me-2"></i>User Management</h2>
                <p class="text-muted">Manage system users</p>
            </div>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createUserModal">
                <i class="bi bi-plus-circle me-2"></i>Create User
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.users') }}" class="row g-3">
                <div class="col-md-4">
                    <label for="user_type" class="form-label">User Type</label>
                    <select name="type" id="user_type" class="form-select form-select-sm">
                        <option value="">All Types</option>
                        <option value="0" {{ request('type') === '0' ? 'selected' : '' }}>Regular User</option>
                        <option value="1" {{ request('type') === '1' ? 'selected' : '' }}>Responder</option>
                        @if(auth()->user()->isSuperAdmin())
                        <option value="2" {{ request('type') === '2' ? 'selected' : '' }}>Admin</option>
                        <option value="3" {{ request('type') === '3' ? 'selected' : '' }}>Super Admin</option>
                        @endif
                    </select>
                </div>
                <div class="col-md-8">
                    <label for="user_search" class="form-label">Search</label>
                    <input type="text" name="search" id="user_search" class="form-control form-control-sm" placeholder="Search by name or email..." value="{{ request('search') }}">
                </div>
                <div class="col-md-12">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-search me-2"></i>Filter
                    </button>
                    <a href="{{ route('admin.users') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-x-circle me-2"></i>Clear
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Users Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            @if($users->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Type</th>
                                <th>Email Verified</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($users as $user)
                            <tr>
                                <td>#{{ $user->id }}</td>
                                <td>{{ $user->name }}</td>
                                <td>{{ $user->email }}</td>
                                <td>
                                    @if($user->type === 0)
                                        <span class="badge bg-secondary">User</span>
                                    @elseif($user->type === 1)
                                        <span class="badge bg-danger">Responder</span>
                                    @elseif($user->type === 2)
                                        <span class="badge bg-primary">Admin</span>
                                    @elseif($user->type === 3)
                                        <span class="badge bg-dark">Super Admin</span>
                                    @endif
                                </td>
                                <td>
                                    @if($user->email_verified_at)
                                        <span class="badge bg-success">Verified</span>
                                    @else
                                        <span class="badge bg-warning">Not Verified</span>
                                    @endif
                                </td>
                                <td>{{ $user->created_at->format('M d, Y') }}</td>
                                <td>
                                    @if(!$user->isSuperAdmin() || auth()->user()->isSuperAdmin())
                                    <button type="button" class="btn btn-sm btn-outline-primary edit-user-btn" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editUserModal"
                                            data-user-id="{{ $user->id }}"
                                            data-user-name="{{ $user->name }}"
                                            data-user-email="{{ $user->email }}"
                                            data-user-type="{{ $user->type }}">
                                        <i class="bi bi-pencil"></i> <span class="d-none d-md-inline">Edit</span>
                                    </button>
                                    @endif
                                    @if($user->id !== auth()->id() && (!$user->isSuperAdmin() || auth()->user()->isSuperAdmin()))
                                    <button type="button" class="btn btn-sm btn-outline-danger delete-user-btn" data-user-id="{{ $user->id }}" data-user-name="{{ $user->name }}">
                                        <i class="bi bi-trash"></i> <span class="d-none d-md-inline">Delete</span>
                                    </button>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div>
                    {{ $users->appends(request()->query())->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="bi bi-inbox fs-1 text-muted"></i>
                    <p class="text-muted mt-3">No users found</p>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Create User Modal -->
<div class="modal fade" id="createUserModal" tabindex="-1" aria-labelledby="createUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createUserModalLabel">
                    <i class="bi bi-person-plus me-2"></i>Create User
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createUserForm">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="modal_name" class="form-label">Name</label>
                        <input type="text" class="form-control form-control-sm" id="modal_name" name="name" value="{{ old('name') }}" required>
                        <div class="invalid-feedback" id="modal_name_error"></div>
                    </div>

                    <div class="mb-3">
                        <label for="modal_email" class="form-label">Email</label>
                        <input type="email" class="form-control form-control-sm" id="modal_email" name="email" value="{{ old('email') }}" required>
                        <div class="invalid-feedback" id="modal_email_error"></div>
                    </div>

                    <div class="mb-3">
                        <label for="modal_password" class="form-label">Password</label>
                        <input type="password" class="form-control form-control-sm" id="modal_password" name="password" required>
                        <div class="invalid-feedback" id="modal_password_error"></div>
                    </div>

                    <div class="mb-3">
                        <label for="modal_password_confirmation" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control form-control-sm" id="modal_password_confirmation" name="password_confirmation" required>
                    </div>

                    <div class="mb-3">
                        <label for="modal_type" class="form-label">User Type</label>
                        <select class="form-select form-select-sm" id="modal_type" name="type" required>
                            <option value="0" {{ old('type') === '0' ? 'selected' : '' }}>Regular User</option>
                            <option value="1" {{ old('type') === '1' ? 'selected' : '' }}>Responder</option>
                            @if(auth()->user()->isSuperAdmin())
                            <option value="2" {{ old('type') === '2' ? 'selected' : '' }}>Admin</option>
                            @endif
                        </select>
                        <div class="invalid-feedback" id="modal_type_error"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm" id="createUserSubmitBtn">
                        <i class="bi bi-check-circle me-2"></i>Create User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editUserModalLabel">
                    <i class="bi bi-pencil me-2"></i>Edit User
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editUserForm">
                @csrf
                @method('PATCH')
                <input type="hidden" id="edit_user_id" name="user_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Name</label>
                        <input type="text" class="form-control form-control-sm" id="edit_name" name="name" required>
                        <div class="invalid-feedback" id="edit_name_error"></div>
                    </div>

                    <div class="mb-3">
                        <label for="edit_email" class="form-label">Email</label>
                        <input type="email" class="form-control form-control-sm" id="edit_email" name="email" required>
                        <div class="invalid-feedback" id="edit_email_error"></div>
                    </div>

                    <div class="mb-3">
                        <label for="edit_password" class="form-label">Password (leave blank to keep current)</label>
                        <input type="password" class="form-control form-control-sm" id="edit_password" name="password">
                        <div class="invalid-feedback" id="edit_password_error"></div>
                    </div>

                    <div class="mb-3">
                        <label for="edit_password_confirmation" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control form-control-sm" id="edit_password_confirmation" name="password_confirmation">
                    </div>

                    <div class="mb-3">
                        <label for="edit_type" class="form-label">User Type</label>
                        <select class="form-select form-select-sm" id="edit_type" name="type" required>
                            <option value="0">Regular User</option>
                            <option value="1">Responder</option>
                            <option value="2" id="edit_type_admin_option">Admin</option>
                            @if(auth()->user()->isSuperAdmin())
                            <option value="3" id="edit_type_super_admin_option">Super Admin</option>
                            @endif
                        </select>
                        <div class="invalid-feedback" id="edit_type_error"></div>
                        @if(!auth()->user()->isSuperAdmin())
                        <small class="form-text text-muted">Note: Only Super Admins can change user type to Admin or Super Admin.</small>
                        @endif
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm" id="editSubmitBtn">
                        <i class="bi bi-check-circle me-2"></i>Update User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@if($errors->any() && old('_token'))
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var createUserModal = new bootstrap.Modal(document.getElementById('createUserModal'));
        createUserModal.show();
    });
</script>
@endif

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle create user form submission
    const createUserForm = document.getElementById('createUserForm');
    const createUserSubmitBtn = document.getElementById('createUserSubmitBtn');
    const createUserModalElement = document.getElementById('createUserModal');
    
    if (createUserForm) {
        createUserForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Clear previous errors
            document.querySelectorAll('#createUserForm .is-invalid').forEach(el => {
                el.classList.remove('is-invalid');
            });
            document.querySelectorAll('#createUserForm .invalid-feedback').forEach(el => {
                el.textContent = '';
            });
            
            // Disable submit button
            createUserSubmitBtn.disabled = true;
            createUserSubmitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Creating...';
            
            const formData = new FormData(createUserForm);
            
            fetch('{{ route('admin.users.store') }}', {
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
                    const createUserModal = bootstrap.Modal.getInstance(createUserModalElement);
                    if (createUserModal) {
                        createUserModal.hide();
                    }
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: data.message || 'User created successfully.',
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
                        text: data.message || 'Failed to create user. Please check the form for errors.',
                        buttonsStyling: false,
                        customClass: {
                            confirmButton: 'btn btn-danger'
                        }
                    });
                    
                    createUserSubmitBtn.disabled = false;
                    createUserSubmitBtn.innerHTML = '<i class="bi bi-check-circle me-2"></i>Create User';
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
                        text: 'An unexpected error occurred. Please try again.'
                    });
                }
                
                createUserSubmitBtn.disabled = false;
                createUserSubmitBtn.innerHTML = '<i class="bi bi-check-circle me-2"></i>Create User';
            });
        });
    }
    
    // Handle edit user button click
    document.querySelectorAll('.edit-user-btn').forEach(button => {
        button.addEventListener('click', function() {
            const userId = this.getAttribute('data-user-id');
            const userName = this.getAttribute('data-user-name');
            const userEmail = this.getAttribute('data-user-email');
            const userType = this.getAttribute('data-user-type');
            
            // Populate form fields
            document.getElementById('edit_user_id').value = userId;
            document.getElementById('edit_name').value = userName;
            document.getElementById('edit_email').value = userEmail;
            
            // Handle user type - show/hide options based on current user's permissions
            const editTypeSelect = document.getElementById('edit_type');
            const adminOption = document.getElementById('edit_type_admin_option');
            const superAdminOption = document.getElementById('edit_type_super_admin_option');
            const isSuperAdmin = {{ auth()->user()->isSuperAdmin() ? 'true' : 'false' }};
            
            if (!isSuperAdmin) {
                if (userType != '2' && userType != '3') {
                    // If editing non-admin user, hide admin option
                    if (adminOption) {
                        adminOption.style.display = 'none';
                    }
                } else {
                    // If editing admin/super admin user, show but they can't change it
                    if (adminOption) {
                        adminOption.style.display = 'block';
                    }
                }
                // Always hide super admin option for non-super admins
                if (superAdminOption) {
                    superAdminOption.style.display = 'none';
                }
            } else {
                // Super admin can see all options
                if (adminOption) {
                    adminOption.style.display = 'block';
                }
                if (superAdminOption) {
                    superAdminOption.style.display = 'block';
                }
            }
            
            editTypeSelect.value = userType;
            document.getElementById('edit_password').value = '';
            document.getElementById('edit_password_confirmation').value = '';
            
            // Clear previous errors
            document.querySelectorAll('#editUserForm .is-invalid').forEach(el => {
                el.classList.remove('is-invalid');
            });
            document.querySelectorAll('#editUserForm .invalid-feedback').forEach(el => {
                el.textContent = '';
            });
        });
    });
    
    // Handle edit form submission
    const editForm = document.getElementById('editUserForm');
    const editSubmitBtn = document.getElementById('editSubmitBtn');
    
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const userId = document.getElementById('edit_user_id').value;
            const formData = new FormData(editForm);
            
            // Clear previous errors
            document.querySelectorAll('#editUserForm .is-invalid').forEach(el => {
                el.classList.remove('is-invalid');
            });
            document.querySelectorAll('#editUserForm .invalid-feedback').forEach(el => {
                el.textContent = '';
            });
            
            // Disable submit button
            editSubmitBtn.disabled = true;
            editSubmitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Updating...';
            
            fetch(`{{ url('/admin/users') }}/${userId}`, {
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
                    const editUserModalElement = document.getElementById('editUserModal');
                    const editUserModal = bootstrap.Modal.getInstance(editUserModalElement);
                    if (editUserModal) {
                        editUserModal.hide();
                    }
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: data.message || 'User updated successfully.',
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
                        text: data.message || 'Failed to update user. Please check the form for errors.',
                        buttonsStyling: false,
                        customClass: {
                            confirmButton: 'btn btn-danger'
                        }
                    });
                    
                    editSubmitBtn.disabled = false;
                    editSubmitBtn.innerHTML = '<i class="bi bi-check-circle me-2"></i>Update User';
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
                        text: error.data.message || 'Please check the form for errors.'
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'An unexpected error occurred. Please try again.'
                    });
                }
                
                editSubmitBtn.disabled = false;
                editSubmitBtn.innerHTML = '<i class="bi bi-check-circle me-2"></i>Update User';
            });
        });
    }
    
    // Delete user with SweetAlert confirmation
    document.querySelectorAll('.delete-user-btn').forEach(button => {
        button.addEventListener('click', function() {
            const userId = this.getAttribute('data-user-id');
            const userName = this.getAttribute('data-user-name');
            
            Swal.fire({
                title: 'Are you sure?',
                text: `Do you want to delete user "${userName}"? This action cannot be undone!`,
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
                    return fetch(`{{ url('/admin/users') }}/${userId}`, {
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
                                throw new Error(data.message || 'Failed to delete user');
                            });
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (!data.success) {
                            throw new Error(data.message || 'Failed to delete user');
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
                        text: result.value.message || 'User has been deleted.',
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

