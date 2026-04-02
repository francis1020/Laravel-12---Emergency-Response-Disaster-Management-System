@extends('layouts.app')

@section('title', 'Response')

@section('content')
    <style>
		.leaflet-control-rotate {
			display: block !important;
		}
	</style>
    <div class="bg-white p-4 rounded shadow-sm">
        <div class="responder-header">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="text-danger text-title fw-bold mb-0 no-wrap">Responding to: {{ ucfirst($report->type) }}</h3>

                <div>
                    @php
                        $responderStatus = $responderAssignment->status ?? null;
                        $isPrimary = $responderAssignment && $responderAssignment->role === 'primary';
                    @endphp
                    
                    <button class="btn btn-sm btn-success" id="responseBtn" style="{{ ($responderStatus === null || $responderStatus === 'cancelled') ? '' : 'display: none;' }}">
                        Response
                    </button>

                    <button class="btn btn-sm btn-primary" id="dispatchBtn" style="{{ $responderStatus === 'assigned' ? '' : 'display: none;'}}">
                        Dispatch
                    </button>

                    <button class="btn btn-sm btn-warning" id="inProgressBtn" style="{{ $responderStatus === 'en_route' ? '' : 'display: none;' }}">
                        Arrival & Responding
                    </button>

                    <button class="btn btn-sm btn-info" id="useResourcesBtn" style="{{ $responderStatus === 'on_scene' ? '' : 'display: none;' }}">
                        <i class="bi bi-truck"></i> Use Resources
                    </button>

                    <button class="btn btn-sm btn-success" id="resolveBtn" style="{{ ($responderStatus === 'on_scene' && $isPrimary) ? '' : 'display: none;' }}">
                        Resolve
                    </button>
                    
                    <button class="btn btn-sm btn-danger" id="cancelBtn" style="display: none;">
                        Cancel
                    </button>
                </div>
               
            </div>
            <div class="details mt-2">
                <p class="no-wrap">
                    <strong>Address:</strong> {{ $report->address ?? 'Unknown' }}
                </p>
                <p><strong>Severity:</strong> {{ ucfirst($report->severity_level ?? 'Moderate') }}</p>
                <p class="no-wrap">
                    <strong>Description:</strong> {{ $report->description ?? 'No description' }}
                </p>
            </div>
        </div>

        <div id="responder-map"></div>

        <div id="responder-stats" class="row g-2" style="{{ ($responderAssignment && in_array($responderAssignment->status, ['en_route', 'on_scene'])) ? '' : 'display: none;' }}">
            <div class="col-md-4">
                <div class="stats-card text-center">
                    <div class="stat-label">Speed</div>
                    <div class="stat-value" id="speedValue">0 km/h</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card text-center">
                    <div class="stat-label">Distance to Destination</div>
                    <div class="stat-value" id="distanceValue">-- km</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card text-center">
                    <div class="stat-label">Estimated Time of Arrival</div>
                    <div class="stat-value" id="etaValue">-- mins</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const report = @json($report);
        const responderMap = L.map("responder-map", {
            center: [report.latitude, report.longitude],
            zoom: 13,
            zoomControl: false,
            attributionControl: false,
            rotate: true, // ✅ enable rotation
            touchRotate: true, // allow pinch-rotate on mobile
        });

        L.tileLayer(selectedStyle.url, {
            attribution: selectedStyle.attribution,
        }).addTo(responderMap);

        const incidentMarker = L.marker([report.latitude, report.longitude], {
            icon: createCustomMarker(report.type, report.severity_level),
        })
            .addTo(responderMap)
            .bindPopup(`<b>${report.type}</b><br>${report.address || ""}`, {
                offset: [0, -25],
            });

        let responderMarker = null;
        let routeControl = null;
        let hasInitialZoom = false; // Track if initial zoom has been done
        let locationWatchId = null; // Track GPS watch ID for cleanup

        const speedEl = document.getElementById("speedValue");
        const distanceEl = document.getElementById("distanceValue");
        const etaEl = document.getElementById("etaValue");

        let lastHeading = 0;
        let lastPosition = null;
        
        // Function to stop location tracking
        function stopLocationTracking() {
            if (locationWatchId !== null && navigator.geolocation) {
                navigator.geolocation.clearWatch(locationWatchId);
                locationWatchId = null;
            }
            if (testInterval) {
                clearInterval(testInterval);
                testInterval = null;
            }
            // Hide stats when location tracking stops
            $('#responder-stats').css('display', 'none');
        }
        @php
            // Map responder status to report status for compatibility
            $mappedStatus = 'pending';
            if ($responderAssignment) {
                $statusMap = [
                    'assigned' => 'acknowledged',
                    'en_route' => 'dispatched',
                    'on_scene' => 'in_progress',
                    'completed' => 'resolved',
                    'cancelled' => 'cancelled',
                ];
                $mappedStatus = $statusMap[$responderAssignment->status] ?? 'pending';
            }
        @endphp
        let currentStatus = '{{ $mappedStatus }}';
        let responderStatus = '{{ $responderAssignment->status ?? 'null' }}';
        let isPrimary = {{ ($responderAssignment && $responderAssignment->role === 'primary') ? 'true' : 'false' }};
        
        // Test mode variables
        let testFlag = false; // Set to true to enable test mode
        let testInterval = null;
        let testRouteCoordinates = [];
        let testCurrentIndex = 0;
        let testWatchId = null;
        
        // Function to update cancel button visibility based on current status
        function updateCancelButtonVisibility() {
            const cancelBtn = document.getElementById('cancelBtn');
            if (!cancelBtn) return;
            
            // Primary responders cannot cancel - always hide the button
            if (isPrimary) {
                cancelBtn.style.display = 'none';
            } else {
                // For secondary responders, show unless completed, cancelled, or null
                if (responderStatus && responderStatus !== 'null' && responderStatus !== 'cancelled' && responderStatus !== 'completed') {
                    cancelBtn.style.display = 'inline-block';
                    cancelBtn.disabled = false;
                } else {
                    cancelBtn.style.display = 'none';
                }
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            if (responderStatus === 'en_route' || responderStatus === 'on_scene') {
                responderLocation();
            }

            const responseBtn = document.getElementById('responseBtn');
            const dispatchBtn = document.getElementById('dispatchBtn');
            const inProgressBtn = document.getElementById('inProgressBtn');
            const resolveBtn = document.getElementById('resolveBtn');
            const cancelBtn = document.getElementById('cancelBtn');
            
            // Initialize cancel button visibility on page load
            updateCancelButtonVisibility();

            responseBtn.addEventListener('click', function () {
                if (responderStatus && responderStatus !== 'null' && responderStatus !== 'cancelled') {
                    Swal.fire({
                        icon: 'info',
                        title: 'Info',
                        text: 'You have already responded to this report.',
                        buttonsStyling: false,
                        customClass: {
                            confirmButton: 'btn btn-info'
                        }
                    });
                    return;
                }
                
                // Check if report has a primary responder
                fetch(`/reports/{{ $report->id }}/responders`)
                    .then(response => response.json())
                    .then(data => {
                        const hasPrimaryResponder = data.status === 'success' && data.responders && 
                            data.responders.some(r => r.role === 'primary');
                        
                        // If no primary responder exists, this responder will become primary
                        const willBePrimary = !hasPrimaryResponder;
                        
                        // Store willBePrimary in a variable accessible to the success handler
                        window.willBePrimaryAfterResponse = willBePrimary;
                        
                        // Build HTML content
                        let htmlContent = `
                            <p>You are about to respond to this emergency report.</p>
                            <textarea id="responseNotes" class="swal2-textarea" placeholder="Enter response notes..."></textarea>
                        `;
                        
                        // Add checkbox warning if responder will become primary
                        if (willBePrimary) {
                            htmlContent += `
                                <div class="alert alert-warning mt-3 mb-0" role="alert">
                                    <strong>Important:</strong> You will be assigned as the <strong>Primary Responder</strong> for this report.
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="checkbox" id="acknowledgePrimary" style="cursor: pointer;">
                                        <label class="form-check-label" for="acknowledgePrimary" style="cursor: pointer;">
                                            I understand that primary responders cannot cancel their response
                                        </label>
                                    </div>
                                </div>
                            `;
                        }
                        
                        Swal.fire({
                            title: 'Respond to this report?',
                            html: htmlContent,
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'Respond',
                            cancelButtonText: 'Close',
                            buttonsStyling: false,
                            customClass: {
                                confirmButton: 'btn btn-success',
                                cancelButton: 'btn btn-secondary',
                                popup: 'text-start'
                            },
                            reverseButtons: true,
                            didOpen: () => {
                                // If checkbox exists, disable confirm button until checked
                                const checkbox = document.getElementById('acknowledgePrimary');
                                if (checkbox) {
                                    const confirmBtn = Swal.getConfirmButton();
                                    confirmBtn.disabled = true;
                                    confirmBtn.style.opacity = '0.5';
                                    confirmBtn.style.cursor = 'not-allowed';
                                    
                                    checkbox.addEventListener('change', function() {
                                        if (this.checked) {
                                            confirmBtn.disabled = false;
                                            confirmBtn.style.opacity = '1';
                                            confirmBtn.style.cursor = 'pointer';
                                        } else {
                                            confirmBtn.disabled = true;
                                            confirmBtn.style.opacity = '0.5';
                                            confirmBtn.style.cursor = 'not-allowed';
                                        }
                                    });
                                }
                            },
                            preConfirm: () => {
                                const notes = document.getElementById('responseNotes').value.trim();
                                if (!notes) {
                                    Swal.showValidationMessage('Please enter your response notes.');
                                    return false;
                                }
                                
                                // Check if checkbox is required and checked
                                if (willBePrimary) {
                                    const checkbox = document.getElementById('acknowledgePrimary');
                                    if (!checkbox || !checkbox.checked) {
                                        Swal.showValidationMessage('Please acknowledge that you understand primary responders cannot cancel.');
                                        return false;
                                    }
                                }
                                
                                return notes; // Pass notes to the next .then() block
                            }
                        }).then((result) => {
                            if (result.isConfirmed) {
                        const responseNotes = result.value;

                        responseBtn.disabled = true;
                        responseBtn.textContent = 'Processing...';

                        $.ajax({
                            url: '/responder/respond',
                            method: 'POST',
                            data: {
                                type_of_response: 'response',
                                report_id: {{ $report->id }},
                                responder_id: {{ Auth::id() }},
                                responding_unit: '{{ Auth::user()->responderDetail->department ?? 'null' }}',
                                response_notes: responseNotes,
                                _token: '{{ csrf_token() }}'
                            },
                            beforeSend: function () {
                                $('.preloader').fadeIn(100);
                                Swal.fire({
                                    title: 'Processing...',
                                    text: 'Please wait while we update your response.',
                                    allowOutsideClick: false,
                                    didOpen: () => {
                                        Swal.showLoading();
                                    }
                                });
                            },
                            success: function (response) {
                                if (response.status === 'success') {
                                    currentStatus = 'acknowledged';
                                    responderStatus = 'assigned'; // Update responder status
                                    
                                    // Update isPrimary if this responder became primary
                                    if (window.willBePrimaryAfterResponse === true) {
                                        isPrimary = true;
                                    }
                                    
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Success',
                                        text: 'You have responded to this report.',
                                        buttonsStyling: false,
                                        customClass: {
                                            confirmButton: 'btn btn-success'
                                        }
                                    });
                                    responseBtn.style.display = 'none';
                                    dispatchBtn.style.display = 'inline-block';
                                    updateCancelButtonVisibility(); // Update cancel button visibility
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error',
                                        text: response.message || 'Failed to update report.',
                                        buttonsStyling: false,
                                        customClass: {
                                            confirmButton: 'btn btn-danger'
                                        }
                                    });
                                }
                            },
                            error: function (xhr) {
                                let errorMessage = 'An unexpected error occurred.';
                                try {
                                    const errorData = JSON.parse(xhr.responseText);
                                    errorMessage = errorData.message || errorMessage;
                                } catch (e) {
                                    if (xhr.responseJSON && xhr.responseJSON.message) {
                                        errorMessage = xhr.responseJSON.message;
                                    } else if (xhr.responseText) {
                                        errorMessage = xhr.responseText;
                                    }
                                }
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: errorMessage,
                                    buttonsStyling: false,
                                    customClass: {
                                        confirmButton: 'btn btn-danger'
                                    }
                                });
                                console.error('Error:', xhr.responseText);
                            },
                            complete: function () {
                                $('.preloader').fadeOut(100);
                                responseBtn.disabled = false;
                                responseBtn.textContent = 'Response';
                            }
                        });
                            }
                        });
                    })
                    .catch(error => {
                        console.error('Error checking responders:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to check responder status. Please try again.',
                            buttonsStyling: false,
                            customClass: {
                                confirmButton: 'btn btn-danger'
                            }
                        });
                    });
            });

            dispatchBtn.addEventListener('click', function () {
                if (responderStatus !== 'assigned') {
                    Swal.fire({
                        icon: 'info',
                        title: 'Info',
                        text: 'You need to respond to the report first.',
                        buttonsStyling: false,
                        customClass: {
                            confirmButton: 'btn btn-info'
                        }
                    });
                    return;
                }
                Swal.fire({
                    title: 'Are you sure?',
                    text: 'You are about to dispatch to this emergency report.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Continue',
                    cancelButtonText: 'Cancel',
                    customClass: {
                        confirmButton: 'btn btn-success',
                        cancelButton: 'btn btn-secondary'
                    },
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '/responder/respond',
                            method: 'POST',
                            data: {
                                type_of_response: 'dispatch',
                                report_id: {{ $report->id }},
                                responder_id: {{ Auth::id() }},
                                _token: '{{ csrf_token() }}'
                            },
                            beforeSend: function () {
                                $('.preloader').fadeIn(100);
                                Swal.fire({
                                    title: 'Processing...',
                                    text: 'Please wait while we update your status.',
                                    allowOutsideClick: false,
                                    didOpen: () => {
                                        Swal.showLoading();
                                    }
                                });
                            },
                            success: function (response) {
                                $('#responder-stats').css('display', '');
                                currentStatus = 'dispatched';
                                responderStatus = 'en_route';

                                dispatchBtn.disabled = true;
                                dispatchBtn.style.display = 'none';
                                inProgressBtn.style.display = 'inline-block';
                                updateCancelButtonVisibility(); // Update cancel button visibility

                                if (response.status === 'success') {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Success',
                                        text: 'Status updated to Dispatched.',
                                        buttonsStyling: false,
                                        customClass: {
                                            confirmButton: 'btn btn-success'
                                        }
                                    });
                                    // Send location immediately when dispatched (for both primary and secondary responders)
                                    if (navigator.geolocation) {
                                        navigator.geolocation.getCurrentPosition(function(position) {
                                            const data = {
                                                latitude: position.coords.latitude,
                                                longitude: position.coords.longitude,
                                                report_id: {{ $report->id }},
                                                responder_id: {{ Auth::id() }},
                                                responder_name: '{{ Auth::user()->name }}',
                                                severity_level: '{{ $report->severity_level }}'
                                            };
                                            sendLocation(data);
                                        }, function(error) {
                                            console.error('Error getting location:', error);
                                            // Still start location tracking
                                            responderLocation();
                                        });
                                    }
                                    responderLocation();
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error',
                                        text: response.message || 'Failed to update report.',
                                        buttonsStyling: false,
                                        customClass: {
                                            confirmButton: 'btn btn-danger'
                                        }
                                    });
                                }
                            },
                            error: function (xhr) {
                                let errorMessage = 'An unexpected error occurred.';
                                try {
                                    const errorData = JSON.parse(xhr.responseText);
                                    errorMessage = errorData.message || errorMessage;
                                } catch (e) {
                                    if (xhr.responseJSON && xhr.responseJSON.message) {
                                        errorMessage = xhr.responseJSON.message;
                                    } else if (xhr.responseText) {
                                        errorMessage = xhr.responseText;
                                    }
                                }
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: errorMessage,
                                    buttonsStyling: false,
                                    customClass: {
                                        confirmButton: 'btn btn-danger'
                                    }
                                });
                                console.error('Error:', xhr.responseText);
                            },
                            complete: function () {
                                $('.preloader').fadeOut(100);
                                dispatchBtn.disabled = false;
                                dispatchBtn.textContent = 'Dispatch';
                            }
                        });
                    }
                });
            });

            inProgressBtn.addEventListener('click', function () {
                if (responderStatus !== 'en_route') {
                    Swal.fire({
                        icon: 'info',
                        title: 'Info',
                        text: 'You need to dispatch to the report first.',
                        buttonsStyling: false,
                        customClass: {
                            confirmButton: 'btn btn-info'
                        }
                    });
                    return;
                }

                // Validate distance based on existing distanceValue element
                function validateAndProceed() {
                    // Get distance from the distanceValue element (already calculated and displayed)
                    const distanceText = distanceEl.textContent.trim();
                    
                    // Parse distance - format is like "2.5 km" or "-- km"
                    let distanceInMeters = 0;
                    
                    if (distanceText && distanceText !== '-- km') {
                        // Extract number from text (e.g., "2.5 km" -> 2.5)
                        const distanceMatch = distanceText.match(/([\d.]+)/);
                        if (distanceMatch) {
                            const distanceKm = parseFloat(distanceMatch[1]);
                            // Convert km to meters
                            distanceInMeters = distanceKm * 1000;
                        }
                    }
                    
                    const distanceRounded = Math.round(distanceInMeters);

                    // If distance is more than 100 meters, show confirmation
                    if (distanceInMeters > 100) {
                        Swal.fire({
                            title: 'Distance Warning',
                            html: `You are currently <strong>${distanceRounded} meters</strong> away from the emergency location.<br><br>Are you sure you want to proceed to arrival?`,
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'Yes, Proceed',
                            cancelButtonText: 'Cancel',
                    buttonsStyling: false,
                    customClass: {
                        confirmButton: 'btn btn-warning',
                        cancelButton: 'btn btn-secondary'
                    },
                            reverseButtons: true
                        }).then((result) => {
                            if (result.isConfirmed) {
                                proceedWithArrival();
                            }
                        });
                    } else {
                        // Distance is within 100 meters, proceed normally
                        Swal.fire({
                            title: 'Confirm Arrival & Responding',
                            text: 'You are about to mark your status as arrived and responding.',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'Confirm',
                            cancelButtonText: 'Cancel',
                            buttonsStyling: false,
                            customClass: {
                                confirmButton: 'btn btn-success',
                                cancelButton: 'btn btn-secondary'
                            },
                            reverseButtons: true
                        }).then((result) => {
                            if (result.isConfirmed) {
                                proceedWithArrival();
                            }
                        });
                    }
                }

                function proceedWithArrival() {
                    $.ajax({
                        url: '/responder/respond',
                        method: 'POST',
                        data: {
                            type_of_response: 'arrival',
                            report_id: {{ $report->id }},
                            responder_id: {{ Auth::id() }},
                            _token: '{{ csrf_token() }}'
                        },
                        beforeSend: function () {
                            $('.preloader').fadeIn(100);
                            Swal.fire({
                                title: 'Processing...',
                                text: 'Please wait while we update your status.',
                                allowOutsideClick: false,
                                didOpen: () => {
                                    Swal.showLoading();
                                }
                            });
                        },
                        success: function (response) {
                            if (response.status === 'success') {
                                currentStatus = 'in_progress';
                                responderStatus = 'on_scene';

                                // Location tracking continues for 'on_scene' status
                                // Stats are hidden when on scene (no longer en route)
                                $('#responder-stats').css('display', 'none');

                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success',
                                    text: 'Status updated to In Progress.',
                                    buttonsStyling: false,
                                    customClass: {
                                        confirmButton: 'btn btn-success'
                                    }
                                });

                                inProgressBtn.disabled = true;
                                inProgressBtn.style.display = 'none';
                                // Show "Use Resources" button after arrival
                                document.getElementById('useResourcesBtn').style.display = 'inline-block';
                                // Only show resolve button if responder is primary
                                if (isPrimary) {
                                    resolveBtn.style.display = 'inline-block';
                                }
                                updateCancelButtonVisibility(); // Update cancel button visibility
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: response.message || 'Failed to update report.',
                                    buttonsStyling: false,
                                    customClass: {
                                        confirmButton: 'btn btn-danger'
                                    }
                                });
                            }
                        },
                        error: function (xhr) {
                            let errorMessage = 'An unexpected error occurred.';
                            try {
                                const errorData = JSON.parse(xhr.responseText);
                                errorMessage = errorData.message || errorMessage;
                            } catch (e) {
                                if (xhr.responseJSON && xhr.responseJSON.message) {
                                    errorMessage = xhr.responseJSON.message;
                                } else if (xhr.responseText) {
                                    errorMessage = xhr.responseText;
                                }
                            }
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: errorMessage,
                                buttonsStyling: false,
                                customClass: {
                                    confirmButton: 'btn btn-danger'
                                }
                            });
                            console.error('Error:', xhr.responseText);
                        },
                        complete: function () {
                            $('.preloader').fadeOut(100);
                            inProgressBtn.disabled = false;
                            inProgressBtn.textContent = 'Arrival & Responding';
                        }
                    });
                }

                // Start the validation process
                validateAndProceed();
            });
            
            resolveBtn.addEventListener('click', function () {
                // Only primary responder can resolve
                if (!isPrimary) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Access Denied',
                        text: 'Only the primary responder can resolve this report.',
                        buttonsStyling: false,
                        customClass: {
                            confirmButton: 'btn btn-danger'
                        }
                    });
                    return;
                }
                
                if (responderStatus !== 'on_scene') {
                    Swal.fire({
                        icon: 'info',
                        title: 'Info',
                        text: 'You need to be In Progress to resolve the report.',
                        buttonsStyling: false,
                        customClass: {
                            confirmButton: 'btn btn-info'
                        }
                    });
                    return;
                }
                
                const resolveTitle = 'Resolve Report';
                const resolveText = 'You are about to mark this report as resolved. This will update all responder statuses.';
                
                Swal.fire({
                    title: resolveTitle,
                    text: resolveText,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Resolve',
                    cancelButtonText: 'Cancel',
                    customClass: {
                        confirmButton: 'btn btn-success',
                        cancelButton: 'btn btn-secondary'
                    },
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '/responder/respond',
                            method: 'POST',
                            data: {
                                type_of_response: 'resolve',
                                report_id: {{ $report->id }},
                                responder_id: {{ Auth::id() }},
                                _token: '{{ csrf_token() }}'
                            },
                            beforeSend: function () {
                                $('.preloader').fadeIn(100);
                                Swal.fire({
                                    title: 'Processing...',
                                    text: 'Please wait while we update the report status.',
                                    allowOutsideClick: false,
                                    didOpen: () => {
                                        Swal.showLoading();
                                    }
                                });
                            },
                            success: function (response) {
                                if (response.status === 'success') {
                                    currentStatus = 'resolved';
                                    responderStatus = 'completed';

                                    // Stop location tracking when resolved
                                    stopLocationTracking();

                                    resolveBtn.disabled = true;
                                    resolveBtn.style.display = 'none';
                                    document.getElementById('useResourcesBtn').style.display = 'none';
                                    updateCancelButtonVisibility(); // Update cancel button visibility
                                    responseResolved();

                                    const isPrimary = {{ ($responderAssignment && $responderAssignment->role === 'primary') ? 'true' : 'false' }};
                                    const successMessage = isPrimary 
                                        ? 'The report has been resolved. All responder statuses have been updated.' 
                                        : 'Your response has been marked as completed.';
                                    
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Success',
                                        text: successMessage,
                                        buttonsStyling: false,
                                        customClass: {
                                            confirmButton: 'btn btn-success'
                                        }
                                    }).then((result) => {
                                        if (result.isConfirmed || result.isDismissed) {
                                            window.location.href = '{{ route("dashboard") }}'
                                        }
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error',
                                        text: response.message || 'Failed to update report.',
                                        buttonsStyling: false,
                                        customClass: {
                                            confirmButton: 'btn btn-danger'
                                        }
                                    });
                                }
                            },
                            error: function (xhr) {
                                let errorMessage = 'An unexpected error occurred.';
                                try {
                                    const errorData = JSON.parse(xhr.responseText);
                                    errorMessage = errorData.message || errorMessage;
                                } catch (e) {
                                    if (xhr.responseJSON && xhr.responseJSON.message) {
                                        errorMessage = xhr.responseJSON.message;
                                    } else if (xhr.responseText) {
                                        errorMessage = xhr.responseText;
                                    }
                                }
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: errorMessage,
                                    buttonsStyling: false,
                                    customClass: {
                                        confirmButton: 'btn btn-danger'
                                    }
                                });
                                console.error('Error:', xhr.responseText);
                            },
                            complete: function () {
                                $('.preloader').fadeOut(100);
                                resolveBtn.disabled = false;
                                resolveBtn.textContent = 'Resolve';
                            }
                        });
                    }
                });
            });
        
            cancelBtn.addEventListener('click', function () {
                // Primary responders cannot cancel - show alert with checkbox
                if (isPrimary) {
                    Swal.fire({
                        icon: 'info',
                        title: 'Primary Responder',
                        html: `
                            <p>You are a primary responder. Cancellation is unavailable for primary responders.</p>
                            <div class="form-check mt-3">
                                <input class="form-check-input" type="checkbox" id="acknowledgeCancel" style="cursor: pointer;">
                                <label class="form-check-label" for="acknowledgeCancel" style="cursor: pointer;">
                                    I understand that primary responders cannot cancel their response
                                </label>
                            </div>
                        `,
                        showCancelButton: false,
                        confirmButtonText: 'I Understand',
                        buttonsStyling: false,
                        customClass: {
                            confirmButton: 'btn btn-primary',
                            popup: 'text-start'
                        },
                        didOpen: () => {
                            const checkbox = document.getElementById('acknowledgeCancel');
                            const confirmBtn = Swal.getConfirmButton();
                            
                            // Disable confirm button initially
                            confirmBtn.disabled = true;
                            confirmBtn.style.opacity = '0.5';
                            confirmBtn.style.cursor = 'not-allowed';
                            
                            // Enable confirm button when checkbox is checked
                            checkbox.addEventListener('change', function() {
                                if (this.checked) {
                                    confirmBtn.disabled = false;
                                    confirmBtn.style.opacity = '1';
                                    confirmBtn.style.cursor = 'pointer';
                                } else {
                                    confirmBtn.disabled = true;
                                    confirmBtn.style.opacity = '0.5';
                                    confirmBtn.style.cursor = 'not-allowed';
                                }
                            });
                        }
                    });
                    return;
                }
                
                // For secondary responders, allow cancel unless completed or cancelled
                if (responderStatus === 'completed' || responderStatus === 'cancelled' || responderStatus === 'null' || !responderStatus) {
                    Swal.fire({
                        icon: 'info',
                        title: 'Info',
                        text: 'Cannot cancel at this stage.',
                        buttonsStyling: false,
                        customClass: {
                            confirmButton: 'btn btn-info'
                        }
                    });
                    return;
                }

                Swal.fire({
                    title: 'Cancel Response',
                    text: 'You are about to cancel your response to this report.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Cancel Response',
                    cancelButtonText: 'Close',
                    buttonsStyling: false,
                    customClass: {
                        confirmButton: 'btn btn-danger',
                        cancelButton: 'btn btn-secondary'
                    },
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '/responder/respond',
                            method: 'POST',
                            data: {
                                type_of_response: 'cancel',
                                report_id: {{ $report->id }},
                                responder_id: {{ Auth::id() }},
                                _token: '{{ csrf_token() }}'
                            },
                            beforeSend: function () {
                                $('.preloader').fadeIn(100);
                                Swal.fire({
                                    title: 'Processing...',
                                    text: 'Please wait while we cancel your response.',
                                    allowOutsideClick: false,
                                    didOpen: () => {
                                        Swal.showLoading();
                                    }
                                });
                            },
                            success: function (response) {
                                if (response.status === 'success') {
                                    currentStatus = 'pending';
                                    responderStatus = 'cancelled';

                                    // Stop location tracking when cancelled
                                    stopLocationTracking();
                                    responseResolved();

                                    // Update UI to reflect cancelled state
                                    responseBtn.style.display = 'inline-block';
                                    dispatchBtn.style.display = 'none';
                                    inProgressBtn.style.display = 'none';
                                    resolveBtn.style.display = 'none';
                                    document.getElementById('useResourcesBtn').style.display = 'none';
                                    updateCancelButtonVisibility(); // Hide cancel button

                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Success',
                                        text: 'Your response has been cancelled.',
                                        buttonsStyling: false,
                                        customClass: {
                                            confirmButton: 'btn btn-success'
                                        }
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error',
                                        text: response.message || 'Failed to cancel response.',
                                        buttonsStyling: false,
                                        customClass: {
                                            confirmButton: 'btn btn-danger'
                                        }
                                    });
                                }
                            },
                            error: function (xhr) {
                                let errorMessage = 'An unexpected error occurred.';
                                try {
                                    const errorData = JSON.parse(xhr.responseText);
                                    errorMessage = errorData.message || errorMessage;
                                } catch (e) {
                                    if (xhr.responseJSON && xhr.responseJSON.message) {
                                        errorMessage = xhr.responseJSON.message;
                                    } else if (xhr.responseText) {
                                        errorMessage = xhr.responseText;
                                    }
                                }
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: errorMessage,
                                    buttonsStyling: false,
                                    customClass: {
                                        confirmButton: 'btn btn-danger'
                                    }
                                });
                                console.error('Error:', xhr.responseText);
                            },
                            complete: function () {
                                $('.preloader').fadeOut(100);
                                cancelBtn.disabled = false;
                                cancelBtn.textContent = 'Cancel';
                            }
                        });
                    }
                });
            });
        });
       

        function updateRouteInfoToSend(summary) {
            if (!summary) return;
            const etaMin = Math.round(summary.totalTime / 60);
            return `Estimated time of arrival: ${etaMin} mins`;
        }

        function updateRouteInfo(summary) {
            if (!summary) return;
            const distanceKm = (summary.totalDistance / 1000).toFixed(2);
            const etaMin = Math.round(summary.totalTime / 60);
            distanceEl.textContent = `${distanceKm} km`;
            etaEl.textContent = `${etaMin} mins`;
        }

        function sendLocation(data) {
            fetch('/send-location', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(data)
            })
                .then(response => response.json())
                .then(data => {
                    // console.log('Location sent successfully', data);
                })
                .catch(err => console.error('Error sending location', err));
        }

        function responseResolved() {
            // Stop location tracking
            stopLocationTracking();
            
            // Remove responder marker
            if (responderMarker) {
                responderMap.removeLayer(responderMarker);
                responderMarker = null;
            }

            // Remove route control
            if (routeControl) {
                responderMap.removeControl(routeControl);
                routeControl = null;
            }
        }
        
        // Cleanup on page unload
        window.addEventListener('beforeunload', function() {
            stopLocationTracking();
        });

        // Helper function to calculate bearing between two coordinates
        function calculateBearing(lat1, lon1, lat2, lon2) {
            const dLon = (lon2 - lon1) * Math.PI / 180;
            const lat1Rad = lat1 * Math.PI / 180;
            const lat2Rad = lat2 * Math.PI / 180;
            
            const y = Math.sin(dLon) * Math.cos(lat2Rad);
            const x = Math.cos(lat1Rad) * Math.sin(lat2Rad) - 
                      Math.sin(lat1Rad) * Math.cos(lat2Rad) * Math.cos(dLon);
            
            let bearing = Math.atan2(y, x) * 180 / Math.PI;
            bearing = (bearing + 360) % 360; // Normalize to 0-360
            return bearing;
        }

        // Function to update location (used by both real GPS and test mode)
        function updateResponderLocation(latitude, longitude, heading = null, speed = null) {
            const responderPos = [latitude, longitude];

            // If heading not provided, calculate from previous position
            if (heading === null || heading === undefined || isNaN(heading)) {
                if (lastPosition) {
                    heading = calculateBearing(
                        lastPosition.lat,
                        lastPosition.lng,
                        latitude,
                        longitude
                    );
                } else {
                    heading = lastHeading || 0;
                }
            }
            
            // Update last heading and position
            lastHeading = heading;
            lastPosition = { lat: latitude, lng: longitude };

            // Calculate speed display
            let speedDisplay = testFlag ? '45.0' : (speed !== null ? (speed * 3.6).toFixed(1) : '0');
            if (speedEl) speedEl.textContent = `${speedDisplay} km/h`;

            // Rotate map based on responder's heading so responder always points to top
            // Invert bearing so direction of travel points "up" instead of "down"
            if (!isNaN(heading) && heading !== null && heading !== undefined) {
                const mapBearing = (360 - heading) % 360;
                try {
                    responderMap.setBearing(mapBearing);
                } catch (e) {
                    console.log('Rotation not available:', e);
                }
            }

            // Center map on responder location
            responderMap.panTo([latitude, longitude], { 
                animate: true, 
                duration: 0.5 
            });

            // Zoom to max after initial load when moving (only if not already at max)
            if (hasInitialZoom) {
                const currentZoom = responderMap.getZoom();
                if (currentZoom < 18) {
                    // responderMap.setZoom(18, { animate: true, duration: 1 });
                }
            }

            if (!responderMarker) {
                // Set initial bearing BEFORE creating marker for proper rotation
                // Use responder's heading so responder points to top
                if (!isNaN(heading) && heading !== null && heading !== undefined) {
                    const initialMapBearing = (360 - heading) % 360;
                    try {
                        responderMap.setBearing(initialMapBearing);
                    } catch (e) {
                        console.log('Initial rotation not available:', e);
                    }
                }

                const responderIcon = L.divIcon({
                    html: `
                        <div class="truck-wrapper">
                            <div class="guide-responder bg-primary"></div>
                            <img src="/assets/img/ambulance.png" class="truck-image" />
                        </div>
                    `,
                    className: "",
                    iconSize: [20, 30],
                    iconAnchor: [10, 20],
                });

                responderMarker = L.marker(responderPos, { icon: responderIcon })
                    .addTo(responderMap)
                    .bindPopup("Your current position", {
                        offset: L.point(0, -15),
                    });

                routeControl = L.Routing.control({
                    waypoints: [
                        L.latLng(latitude, longitude),
                        L.latLng(report.latitude, report.longitude),
                    ],
                    addWaypoints: false,
                    draggableWaypoints: false,
                    routeWhileDragging: false,
                    fitSelectedRoutes: false,
                    lineOptions: {
                        styles: [
                            {
                                color: severityColors[report.severity_level] ?? "#007bff",
                                weight: 5,
                            },
                        ],
                    },
                    createMarker: function () {
                        return null;
                    },
                }).addTo(responderMap);

                // Fit bounds to show both incident and responder locations (only on initial load)
                if (!hasInitialZoom) {
                    const bounds = L.latLngBounds([
                        [latitude, longitude],
                        [report.latitude, report.longitude]
                    ]);
                    responderMap.fitBounds(bounds, { 
                        padding: [50, 50],
                        maxZoom: 18
                    });
                    // Reduce zoom by 1 level after fitBounds, then zoom to max after initial load
                    setTimeout(() => {
                        const currentZoom = responderMap.getZoom();
                        responderMap.setZoom(currentZoom - 1, { animate: true, duration: 1 });
                        // After initial load, zoom to max zoom level when moving
                        setTimeout(() => {
                            
                            // Center on responder after zooming to max
                            responderMap.panTo([latitude, longitude], { 
                                animate: true, 
                                duration: 0.5 
                            });

                            setTimeout(() => {
                                responderMap.setZoom(18, { animate: true, duration: 1 });
                            }, 300);
                        }, 300);
                    }, 300);
                    hasInitialZoom = true; // Mark initial zoom as done
                } else {
                    // After initial load, zoom to max when moving (only if not already at max)
                    const currentZoom = responderMap.getZoom();
                    if (currentZoom < 18) {
                        // responderMap.setZoom(18, { animate: true, duration: 1 });
                    }
                }

                routeControl.on("routesfound", function (e) {
                    const route = e.routes[0];
                    const summary = route.summary;
                    const message = `Responding to: ${report.type}, ${report.address || "Unknown"}`;

                    // Store route coordinates for test mode
                    if (testFlag) {
                        // Extract coordinates from route (Leaflet Routing Machine structure)
                        // Try multiple methods to get coordinates
                        if (route.coordinates && Array.isArray(route.coordinates) && route.coordinates.length > 0) {
                            testRouteCoordinates = route.coordinates;
                        } else if (route.coordinatePath && Array.isArray(route.coordinatePath) && route.coordinatePath.length > 0) {
                            testRouteCoordinates = route.coordinatePath;
                        } else if (route.coordinates && route.coordinates.coordinates) {
                            // GeoJSON format
                            testRouteCoordinates = route.coordinates.coordinates.map(coord => L.latLng(coord[1], coord[0]));
                        } else if (e.routes && e.routes[0] && e.routes[0].coordinates) {
                            testRouteCoordinates = e.routes[0].coordinates;
                        } else {
                            // Fallback: extract from route geometry if available
                            console.log('Route structure:', route);
                            console.warn('Could not extract route coordinates for test mode');
                        }
                        
                        if (testRouteCoordinates && testRouteCoordinates.length > 0) {
                            // testCurrentIndex = 0;
                            console.log('Test mode: Starting simulation with', testRouteCoordinates.length, 'coordinates');
                            // Start test mode simulation
                            startTestMode();
                        } else {
                            console.warn('Test mode: No route coordinates available');
                        }
                    }

                    const data = {
                        latitude,
                        longitude,
                        message,
                        summary: updateRouteInfoToSend(summary),
                        report_id: {{ $report->id ?? 0 }},
                        responder_id: {{ Auth::id() ?? 0 }},
                        responder_name: "{{ Auth::user()->name ?? 'Responder' }}",
                        severity_level: "{{ $report->severity_level ?? 'moderate' }}",
                    };

                    updateRouteInfo(summary);
                    if (!testFlag) {
                        sendLocation(data);
                    }
                });
            } else {
                // Update existing marker position
                responderMarker.setLatLng(responderPos);

                // Update route waypoints
                if (routeControl) {
                    routeControl.setWaypoints([
                        L.latLng(latitude, longitude),
                        L.latLng(report.latitude, report.longitude),
                    ]);
                }
            }
        }

        // Test mode function to simulate movement along route
        function startTestMode() {
            if (testInterval) {
                clearInterval(testInterval);
            }

            testInterval = setInterval(function() {
                if (testRouteCoordinates.length === 0 || testCurrentIndex >= testRouteCoordinates.length) {
                    // Route not ready or reached destination
                    if (testCurrentIndex >= testRouteCoordinates.length) {
                        clearInterval(testInterval);
                        console.log('Test mode: Reached destination');
                    }
                    return;
                }

                // Get current coordinate from route (handle both L.LatLng and plain objects)
                const coord = testRouteCoordinates[testCurrentIndex];
                const latitude = coord.lat || coord.latitude;
                const longitude = coord.lng || coord.longitude;

                // Calculate heading to next point
                let heading = null;
                if (testCurrentIndex < testRouteCoordinates.length - 1) {
                    const nextCoord = testRouteCoordinates[testCurrentIndex + 1];
                    const nextLat = nextCoord.lat || nextCoord.latitude;
                    const nextLng = nextCoord.lng || nextCoord.longitude;
                    heading = calculateBearing(
                        latitude,
                        longitude,
                        nextLat,
                        nextLng
                    );
                }
                console.log('testCurrentIndex', testCurrentIndex);
                
                // Update location with simulated speed
                updateResponderLocation(latitude, longitude, heading, 12.5); // ~45 km/h in m/s

                // Move to next point (skip some points to move faster)
                testCurrentIndex += 1; // Move 2 points at a time for smoother movement
            }, 3000); // Update every 2 seconds
        }

        function responderLocation () {
            if (responderStatus === 'en_route' || responderStatus === 'on_scene') {
                // Check if test mode is enabled
                if (testFlag) {
                    // Test mode: get initial position once, then calculate route
                    if (navigator.geolocation) {
                        navigator.geolocation.getCurrentPosition(
                            (pos) => {
                                const latitude = pos.coords.latitude;
                                const longitude = pos.coords.longitude;
                                // Initialize with first position and calculate route
                                updateResponderLocation(latitude, longitude, null);
                            },
                            (err) => {
                                // If GPS fails, use a default position near the report
                                const defaultLat = report.latitude + 0.01;
                                const defaultLng = report.longitude + 0.01;
                                updateResponderLocation(defaultLat, defaultLng, null);
                            },
                            {
                                enableHighAccuracy: true,
                                timeout: 5000,
                                maximumAge: 0
                            }
                        );
                    } else {
                        // No geolocation, use default position
                        const defaultLat = report.latitude + 0.01;
                        const defaultLng = report.longitude + 0.01;
                        updateResponderLocation(defaultLat, defaultLng, null);
                    }
                    return;
                }

                // Normal mode: use real GPS
                if (navigator.geolocation) {
                    // Clear any existing watch before starting a new one
                    if (locationWatchId !== null) {
                        navigator.geolocation.clearWatch(locationWatchId);
                    }
                    
                    locationWatchId = navigator.geolocation.watchPosition(
                        (pos) => {
                            // Check status before updating location
                            if (responderStatus !== 'en_route' && responderStatus !== 'on_scene') {
                                stopLocationTracking();
                                return;
                            }
                            
                            const latitude = pos.coords.latitude;
                            const longitude = pos.coords.longitude;
                            const heading = pos.coords.heading;
                            const speed = pos.coords.speed || null;
                            
                            // Use the shared update function
                            updateResponderLocation(latitude, longitude, heading, speed);
                        },
                        (err) => {
                            alert("Unable to access your location. Please enable GPS.");
                            console.error(err);
                        },
                        {
                            enableHighAccuracy: true,
                            maximumAge: 1000,
                            timeout: 10000,
                        }
                    );
                } else {
                    alert("Geolocation not supported by your browser.");
                }
            } else {
                // Status is not 'en_route' or 'on_scene', stop any active tracking
                stopLocationTracking();
            }
        }

        // Resources functionality
        const useResourcesBtn = document.getElementById('useResourcesBtn');
        if (useResourcesBtn) {
            useResourcesBtn.addEventListener('click', function() {
                loadAvailableResources();
            });
        }

        function loadAvailableResources() {
            $.ajax({
                url: '/resources/available',
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'Accept': 'application/json'
                },
                data: {
                    report_id: {{ $report->id }}
                },
                success: function(response) {
                    if (response.status === 'success') {
                        showResourcesModal(response.availableResources, response.assignedResources);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message || 'Failed to load resources.',
                            buttonsStyling: false,
                            customClass: {
                                confirmButton: 'btn btn-danger'
                            }
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error loading resources:', xhr, status, error);
                    let errorMessage = 'Failed to load resources.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    } else if (xhr.status === 403) {
                        errorMessage = 'You are not authorized to view resources.';
                    } else if (xhr.status === 404) {
                        errorMessage = 'Resources endpoint not found.';
                    } else if (xhr.status === 500) {
                        errorMessage = 'Server error. Please try again later.';
                    }
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: errorMessage,
                        buttonsStyling: false,
                        customClass: {
                            confirmButton: 'btn btn-danger'
                        }
                    });
                }
            });
        }

        function showResourcesModal(availableResources, assignedResources) {
            let html = '<div class="row g-3">';
            
            // Show assigned resources first
            if (assignedResources && assignedResources.length > 0) {
                html += '<div class="col-12"><h6 class="mb-2">My Assigned Resources</h6></div>';
                assignedResources.forEach(resource => {
                    html += `
                        <div class="col-md-6">
                            <div class="card border-success">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1">${resource.name}</h6>
                                            <p class="text-muted small mb-0">${resource.type} ${resource.identifier ? ' - ' + resource.identifier : ''}</p>
                                        </div>
                                        <button class="btn btn-sm btn-danger" onclick="releaseResource(${resource.id})">
                                            <i class="bi bi-x-circle"></i> Release
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                });
                html += '<div class="col-12"><hr><h6 class="mb-2">Available Resources</h6></div>';
            }
            
            // Show available resources
            if (availableResources && availableResources.length > 0) {
                availableResources.forEach(resource => {
                    html += `
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1">${resource.name}</h6>
                                            <p class="text-muted small mb-0">${resource.type} ${resource.identifier ? ' - ' + resource.identifier : ''}</p>
                                            ${resource.description ? '<p class="text-muted small mb-0">' + resource.description.substring(0, 50) + '</p>' : ''}
                                        </div>
                                        <button class="btn btn-sm btn-success" onclick="assignResource(${resource.id})">
                                            <i class="bi bi-check-circle"></i> Assign
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                });
            } else {
                html += '<div class="col-12"><p class="text-muted">No available resources</p></div>';
            }
            
            html += '</div>';

            Swal.fire({
                title: 'Manage Resources',
                html: html,
                width: '800px',
                showCancelButton: false,
                confirmButtonText: 'Close',
                buttonsStyling: false,
                customClass: {
                    confirmButton: 'btn btn-secondary',
                    popup: 'text-start'
                }
            });
        }

        function assignResource(resourceId) {
            $.ajax({
                url: `/resources/${resourceId}/assign`,
                method: 'POST',
                data: {
                    report_id: {{ $report->id }},
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: 'Resource assigned successfully.',
                            buttonsStyling: false,
                            customClass: {
                                confirmButton: 'btn btn-success'
                            }
                        }).then(() => {
                            loadAvailableResources();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message || 'Failed to assign resource.',
                            buttonsStyling: false,
                            customClass: {
                                confirmButton: 'btn btn-danger'
                            }
                        });
                    }
                },
                error: function(xhr) {
                    const message = xhr.responseJSON?.message || 'Failed to assign resource.';
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: message,
                        buttonsStyling: false,
                        customClass: {
                            confirmButton: 'btn btn-danger'
                        }
                    });
                }
            });
        }

        function releaseResource(resourceId) {
            Swal.fire({
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
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `/resources/${resourceId}/release`,
                        method: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            if (response.status === 'success') {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success',
                                    text: 'Resource released successfully.',
                                    buttonsStyling: false,
                                    customClass: {
                                        confirmButton: 'btn btn-success'
                                    }
                                }).then(() => {
                                    loadAvailableResources();
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: response.message || 'Failed to release resource.',
                                    buttonsStyling: false,
                                    customClass: {
                                        confirmButton: 'btn btn-danger'
                                    }
                                });
                            }
                        },
                        error: function(xhr) {
                            const message = xhr.responseJSON?.message || 'Failed to release resource.';
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: message,
                                buttonsStyling: false,
                                customClass: {
                                    confirmButton: 'btn btn-danger'
                                }
                            });
                        }
                    });
                }
            });
        }
    </script>

@endsection