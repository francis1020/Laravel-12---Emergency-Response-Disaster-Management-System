@extends('layouts.app')

@section('title', 'Profile')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-0"><i class="bi bi-person-circle me-2"></i>Profile</h2>
            <p class="text-muted">Update your account profile information</p>
        </div>
    </div>

    <div class="row g-4">
        <!-- Update Profile Information -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-person me-2"></i>Profile Information</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-4">Update your account's profile information and email address.</p>

                    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
                        @csrf
                    </form>

                    <form method="post" action="{{ route('profile.update') }}">
                        @csrf
                        @method('patch')

                        <div class="mb-3">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" 
                                   class="form-control form-control-sm @error('name') is-invalid @enderror" 
                                   id="name" 
                                   name="name" 
                                   value="{{ old('name', $user->name) }}" 
                                   required 
                                   autofocus>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" 
                                   class="form-control form-control-sm @error('email') is-invalid @enderror" 
                                   id="email" 
                                   name="email" 
                                   value="{{ old('email', $user->email) }}" 
                                   required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror

                            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                                <div class="mt-2">
                                    <p class="text-sm text-muted">
                                        Your email address is unverified.
                                        <button form="send-verification" class="btn btn-link p-0 text-decoration-underline">
                                            Click here to re-send the verification email.
                                        </button>
                                    </p>

                                    @if (session('status') === 'verification-link-sent')
                                        <p class="mt-2 text-success small">
                                            A new verification link has been sent to your email address.
                                        </p>
                                    @endif
                                </div>
                            @endif
                        </div>

                        <hr class="my-4">

                        <h6 class="mb-3"><i class="bi bi-info-circle me-2"></i>Additional Information</h6>

                        <div class="mb-3">
                            <label for="user_contact_number" class="form-label">Contact Number</label>
                            <input type="text" 
                                   class="form-control form-control-sm @error('user_contact_number') is-invalid @enderror" 
                                   id="user_contact_number" 
                                   name="user_contact_number" 
                                   value="{{ old('user_contact_number', $user->userDetail?->contact_number ?? '') }}" 
                                   placeholder="09XXXXXXXXX"
                                   maxlength="11">
                            <small class="form-text text-muted">Format: 09XXXXXXXXX (11 digits)</small>
                            @error('user_contact_number')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control form-control-sm @error('address') is-invalid @enderror" 
                                      id="address" 
                                      name="address" 
                                      rows="2">{{ old('address', $user->userDetail?->address ?? '') }}</textarea>
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="birthdate" class="form-label">Birthdate</label>
                                <input type="date" 
                                       class="form-control form-control-sm @error('birthdate') is-invalid @enderror" 
                                       id="birthdate" 
                                       name="birthdate" 
                                       value="{{ old('birthdate', $user->userDetail?->birthdate?->format('Y-m-d') ?? '') }}">
                                @error('birthdate')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="gender" class="form-label">Gender</label>
                                <select class="form-select form-select-sm @error('gender') is-invalid @enderror" 
                                        id="gender" 
                                        name="gender">
                                    <option value="">Select Gender</option>
                                    <option value="male" {{ old('gender', $user->userDetail?->gender ?? '') === 'male' ? 'selected' : '' }}>Male</option>
                                    <option value="female" {{ old('gender', $user->userDetail?->gender ?? '') === 'female' ? 'selected' : '' }}>Female</option>
                                    <option value="other" {{ old('gender', $user->userDetail?->gender ?? '') === 'other' ? 'selected' : '' }}>Other</option>
                                </select>
                                @error('gender')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        @if($user->isResponder())
                        <hr class="my-4">

                        <h6 class="mb-3"><i class="bi bi-shield-check me-2"></i>Responder Information</h6>

                        <div class="mb-3">
                            <label for="department" class="form-label">Department</label>
                            <input type="text" 
                                   class="form-control form-control-sm @error('department') is-invalid @enderror" 
                                   id="department" 
                                   name="department" 
                                   value="{{ old('department', $user->responderDetail?->department ?? '') }}" 
                                   placeholder="e.g., Fire Department, Police, Medical">
                            @error('department')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="station_address" class="form-label">Station Address</label>
                            <textarea class="form-control form-control-sm @error('station_address') is-invalid @enderror" 
                                      id="station_address" 
                                      name="station_address" 
                                      rows="2">{{ old('station_address', $user->responderDetail?->station_address ?? '') }}</textarea>
                            @error('station_address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Office Location</label>
                            <small class="form-text text-muted d-block mb-2">Click on the map or drag the marker to set your office location for auto-assignment</small>
                            
                            <!-- Map Container -->
                            <div id="office_location_map" style="height: 400px; width: 100%; border-radius: 8px; border: 1px solid #dee2e6;"></div>
                            
                            <!-- Hidden Inputs -->
                            <input type="hidden" 
                                   id="office_location_latitude" 
                                   name="office_location_latitude" 
                                   value="{{ old('office_location_latitude', $user->responderDetail?->office_location_latitude ?? '') }}">
                            <input type="hidden" 
                                   id="office_location_longitude" 
                                   name="office_location_longitude" 
                                   value="{{ old('office_location_longitude', $user->responderDetail?->office_location_longitude ?? '') }}">
                            
                            <!-- Display Coordinates -->
                            <div class="mt-2 d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    <i class="bi bi-geo-alt me-1"></i>
                                    <span id="office_coords_display">No location selected</span>
                                </small>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="useCurrentLocationForOffice()">
                                    <i class="bi bi-crosshair me-1"></i>Use Current Location
                                </button>
                            </div>
                            
                            @error('office_location_latitude')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            @error('office_location_longitude')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="vehicle_type" class="form-label">Vehicle Type</label>
                                <input type="text" 
                                       class="form-control form-control-sm @error('vehicle_type') is-invalid @enderror" 
                                       id="vehicle_type" 
                                       name="vehicle_type" 
                                       value="{{ old('vehicle_type', $user->responderDetail?->vehicle_type ?? '') }}" 
                                       placeholder="e.g., Ambulance, Fire Truck">
                                @error('vehicle_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="license_number" class="form-label">License Number</label>
                                <input type="text" 
                                       class="form-control form-control-sm @error('license_number') is-invalid @enderror" 
                                       id="license_number" 
                                       name="license_number" 
                                       value="{{ old('license_number', $user->responderDetail?->license_number ?? '') }}">
                                @error('license_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select form-select-sm @error('status') is-invalid @enderror" 
                                    id="status" 
                                    name="status">
                                <option value="available" {{ old('status', $user->responderDetail?->status ?? 'available') === 'available' ? 'selected' : '' }}>Available</option>
                                <option value="busy" {{ old('status', $user->responderDetail?->status ?? '') === 'busy' ? 'selected' : '' }}>Busy</option>
                                <option value="offline" {{ old('status', $user->responderDetail?->status ?? '') === 'offline' ? 'selected' : '' }}>Offline</option>
                                <option value="unavailable" {{ old('status', $user->responderDetail?->status ?? '') === 'unavailable' ? 'selected' : '' }}>Unavailable</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       name="auto_assign" 
                                       value="1" 
                                       id="auto_assign"
                                       {{ old('auto_assign', $user->responderDetail?->auto_assign ?? false) ? 'checked' : '' }}>
                                <label class="form-check-label" for="auto_assign">
                                    <strong>Enable Auto-Assign</strong>
                                </label>
                                <small class="form-text text-muted d-block">When enabled, you will be automatically assigned to emergency reports based on proximity and emergency type match</small>
                            </div>
                            @error('auto_assign')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Emergency Types I Can Respond To</label>
                            <small class="form-text text-muted d-block mb-2">Select the types of emergencies you are qualified to respond to</small>
                            <div class="row g-2">
                                @foreach(\App\Models\EmergencyType::where('status', 1)->orderBy('name')->get() as $type)
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" 
                                               type="checkbox" 
                                               name="emergency_types[]" 
                                               value="{{ $type->code }}" 
                                               id="emergency_type_{{ $type->code }}"
                                               {{ in_array($type->code, old('emergency_types', $user->responderDetail?->emergency_types ?? [])) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="emergency_type_{{ $type->code }}">
                                            <i class="{{ $type->icon }} me-1"></i> {{ $type->name }}
                                        </label>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            @error('emergency_types')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        @endif

                        <div class="d-flex align-items-center gap-2">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="bi bi-save me-2"></i>Save
                            </button>

                            @if (session('status') === 'profile-updated')
                                <span class="text-success small">
                                    <i class="bi bi-check-circle me-1"></i>Saved.
                                </span>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Update Password -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-lock me-2"></i>Update Password</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-4">Ensure your account is using a long, random password to stay secure.</p>

                    <form method="post" action="{{ route('password.update') }}">
                        @csrf
                        @method('put')

                        <div class="mb-3">
                            <label for="update_password_current_password" class="form-label">Current Password</label>
                            <input type="password" 
                                   class="form-control form-control-sm @error('current_password', 'updatePassword') is-invalid @enderror" 
                                   id="update_password_current_password" 
                                   name="current_password" 
                                   autocomplete="current-password">
                            @if($errors->updatePassword->has('current_password'))
                                <div class="invalid-feedback d-block">{{ $errors->updatePassword->first('current_password') }}</div>
                            @endif
                        </div>

                        <div class="mb-3">
                            <label for="update_password_password" class="form-label">New Password</label>
                            <input type="password" 
                                   class="form-control form-control-sm @error('password', 'updatePassword') is-invalid @enderror" 
                                   id="update_password_password" 
                                   name="password" 
                                   autocomplete="new-password">
                            @if($errors->updatePassword->has('password'))
                                <div class="invalid-feedback d-block">{{ $errors->updatePassword->first('password') }}</div>
                            @endif
                        </div>

                        <div class="mb-3">
                            <label for="update_password_password_confirmation" class="form-label">Confirm Password</label>
                            <input type="password" 
                                   class="form-control form-control-sm" 
                                   id="update_password_password_confirmation" 
                                   name="password_confirmation" 
                                   autocomplete="new-password">
                        </div>

                        <div class="d-flex align-items-center gap-2">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="bi bi-save me-2"></i>Save
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Delete Account -->
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-trash me-2"></i>Delete Account</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-4">
                        Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.
                    </p>

                    <button type="button" 
                            class="btn btn-danger btn-sm" 
                            data-bs-toggle="modal" 
                            data-bs-target="#confirmUserDeletion">
                        <i class="bi bi-trash me-2"></i>Delete Account
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Account Modal -->
<div class="modal fade" id="confirmUserDeletion" tabindex="-1" aria-labelledby="confirmUserDeletionLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmUserDeletionLabel">Delete Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="{{ route('profile.destroy') }}" id="deleteAccountForm">
                @csrf
                @method('delete')

                <div class="modal-body">
                    <p>Are you sure you want to delete your account?</p>
                    <p class="text-muted small">
                        Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.
                    </p>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" 
                               class="form-control form-control-sm @if($errors->userDeletion->has('password')) is-invalid @endif" 
                               id="password" 
                               name="password" 
                               placeholder="Enter your password" 
                               required>
                        @if($errors->userDeletion->has('password'))
                            <div class="invalid-feedback d-block">{{ $errors->userDeletion->first('password') }}</div>
                        @endif
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger btn-sm" id="deleteAccountBtn">
                        <i class="bi bi-trash me-2"></i>Delete Account
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
let officeLocationMap = null;
let officeLocationMarker = null;

function initOfficeLocationMap() {
    // Get saved coordinates or use current location
    const savedLat = parseFloat(document.getElementById('office_location_latitude').value) || null;
    const savedLng = parseFloat(document.getElementById('office_location_longitude').value) || null;
    
    // Default location (Cebu City) if no saved data
    const defaultLat = 10.3157;
    const defaultLng = 123.8854;
    
    // Initialize map
    officeLocationMap = L.map('office_location_map', {
        center: savedLat && savedLng ? [savedLat, savedLng] : [defaultLat, defaultLng],
        zoom: savedLat && savedLng ? 15 : 13,
        zoomControl: true,
        attributionControl: false
    });

    // Add tile layer
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19
    }).addTo(officeLocationMap);

    // Add marker if coordinates exist
    if (savedLat && savedLng) {
        officeLocationMarker = L.marker([savedLat, savedLng], { draggable: true }).addTo(officeLocationMap);
        updateOfficeCoordsDisplay(savedLat, savedLng);
    } else {
        // Try to get current location, otherwise use default
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    officeLocationMap.setView([lat, lng], 15);
                    officeLocationMarker = L.marker([lat, lng], { draggable: true }).addTo(officeLocationMap);
                    updateOfficeCoords(lat, lng);
                },
                function(error) {
                    // Use default location if geolocation fails
                    officeLocationMarker = L.marker([defaultLat, defaultLng], { draggable: true }).addTo(officeLocationMap);
                    updateOfficeCoords(defaultLat, defaultLng);
                }
            );
        } else {
            // Use default location if geolocation not supported
            officeLocationMarker = L.marker([defaultLat, defaultLng], { draggable: true }).addTo(officeLocationMap);
            updateOfficeCoords(defaultLat, defaultLng);
        }
    }

    // Marker drag handler
    if (officeLocationMarker) {
        officeLocationMarker.on('dragend', function() {
            const pos = officeLocationMarker.getLatLng();
            updateOfficeCoords(pos.lat, pos.lng);
        });
    }

    // Map click handler
    officeLocationMap.on('click', function(e) {
        const { lat, lng } = e.latlng;
        if (officeLocationMarker) {
            officeLocationMarker.setLatLng([lat, lng]);
        } else {
            officeLocationMarker = L.marker([lat, lng], { draggable: true }).addTo(officeLocationMap);
        }
        updateOfficeCoords(lat, lng);
    });
}

function updateOfficeCoords(lat, lng) {
    document.getElementById('office_location_latitude').value = lat.toFixed(7);
    document.getElementById('office_location_longitude').value = lng.toFixed(7);
    updateOfficeCoordsDisplay(lat, lng);
}

function updateOfficeCoordsDisplay(lat, lng) {
    const display = document.getElementById('office_coords_display');
    if (display) {
        display.textContent = `${lat.toFixed(7)}, ${lng.toFixed(7)}`;
    }
}

function useCurrentLocationForOffice() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            function(position) {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                
                if (officeLocationMap) {
                    officeLocationMap.setView([lat, lng], 15);
                }
                
                if (officeLocationMarker) {
                    officeLocationMarker.setLatLng([lat, lng]);
                } else {
                    officeLocationMarker = L.marker([lat, lng], { draggable: true }).addTo(officeLocationMap);
                }
                
                updateOfficeCoords(lat, lng);
            },
            function(error) {
                alert('Unable to retrieve your location. Please click on the map to set your office location.');
            }
        );
    } else {
        alert('Geolocation is not supported by your browser. Please click on the map to set your office location.');
    }
}

// Initialize map when page loads
document.addEventListener('DOMContentLoaded', function() {
    initOfficeLocationMap();
    
    // Show SweetAlert notification if profile was updated
    @if (session('status') === 'profile-updated')
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: 'Saved!',
                text: 'Your profile has been updated successfully.',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
            });
        }
    @endif

    // Show SweetAlert notification if password was updated
    @if (session('status') === 'password-updated')
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: 'Password Updated!',
                text: 'Your password has been updated successfully.',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
            });
        }
    @endif

    // Handle account deletion with password validation and SweetAlert confirmation
    const deleteAccountForm = document.getElementById('deleteAccountForm');
    const deleteAccountBtn = document.getElementById('deleteAccountBtn');
    
    if (deleteAccountForm && deleteAccountBtn) {
        deleteAccountForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const password = document.getElementById('password').value;
            
            if (!password) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Password Required',
                        text: 'Please enter your password to confirm account deletion.',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true
                    });
                }
                return;
            }

            // Show loading while validating password
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Validating Password...',
                    text: 'Please wait while we verify your password.',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
            }

            // Validate password first via AJAX
            fetch('{{ route("profile.validate-password") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ password: password })
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => {
                        throw err;
                    });
                }
                return response.json();
            })
            .then(data => {
                Swal.close();
                
                if (data.valid) {
                    // Password is correct, show confirmation
                    Swal.fire({
                        icon: 'warning',
                        title: 'Are you sure?',
                        html: '<p>This action cannot be undone. All your data will be permanently deleted.</p>',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Yes, delete my account',
                        cancelButtonText: 'Cancel',
                        reverseButtons: true
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Show loading state
                            Swal.fire({
                                title: 'Deleting Account...',
                                text: 'Please wait while we delete your account.',
                                allowOutsideClick: false,
                                allowEscapeKey: false,
                                showConfirmButton: false,
                                didOpen: () => {
                                    Swal.showLoading();
                                }
                            });
                            
                            // Submit the form
                            deleteAccountForm.submit();
                        }
                    });
                }
            })
            .catch(error => {
                Swal.close();
                
                // Handle validation errors from Laravel
                let errorMessage = 'The password you entered is incorrect. Please try again.';
                
                if (error.errors && error.errors.password) {
                    errorMessage = Array.isArray(error.errors.password) 
                        ? error.errors.password[0] 
                        : error.errors.password;
                } else if (error.message) {
                    errorMessage = error.message;
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'Incorrect Password',
                    text: errorMessage,
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true
                });
            });
        });
    }
});
</script>
@endpush
@endsection
