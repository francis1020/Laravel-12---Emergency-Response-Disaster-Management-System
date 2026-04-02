@extends('layouts.app')

@section('title', 'Emergency Reports Map')

@section('content')
	<div class="map-container">
		<!-- Settings Dropdown -->
		<div class="dropdown settings-dropdown">
			<div class="settings-icon" data-bs-toggle="dropdown" data-bs-placement="left" title="Open Settings"
				data-bs-toggle="tooltip" role="button" aria-expanded="false">
				<i class="bi bi-gear-fill fs-12"></i>
			</div>

			<ul class="dropdown-menu dropdown-menu-end p-3 shadow" style="margin-left: 20px !important;">
				<li class="mb-3">
					<label for="mapStyleSelect" class="form-label fw-semibold mb-1">
						<i class="bi bi-map"></i> Map Style
					</label>
					<select class="form-select form-select-sm" id="mapStyleSelect"></select>
				</li>
				@php
					$isValidatedUser = Auth::check() && Auth::user() && Auth::user()->email_verified_at !== null && !Auth::user()->isResponder();
					$isValidatedResponder = Auth::check() && Auth::user() && Auth::user()->isResponder() && Auth::user()->email_verified_at !== null;
				@endphp
				@if (!$isValidatedResponder)
				<li class="mb-3">
					<div class="form-check form-switch">
						<input class="form-check-input" type="checkbox" id="hideMyLocation" />
						<label class="form-check-label fw-semibold" for="hideMyLocation">
							<i class="bi bi-geo-alt"></i> Hide My Location
						</label>
					</div>
				</li>
				@endif
				<li @class([
					'mb-3' => $isValidatedUser || $isValidatedResponder,
				])>
					<div class="form-check form-switch">
						<input class="form-check-input" type="checkbox" id="hideLegend" />
						<label class="form-check-label fw-semibold" for="hideLegend">
							<i class="bi bi-eye"></i> Hide Legend
						</label>
					</div>
				</li>
				@if ($isValidatedUser)
				<li>
					<div class="form-check form-switch">
						<input class="form-check-input" type="checkbox" id="showOnlyMyReport" />
						<label class="form-check-label fw-semibold" for="showOnlyMyReport">
							<i class="bi bi-eye"></i> Show Only My Report
						</label>
					</div>
				</li>
				@endif
				@if ($isValidatedResponder)
				<li class="mb-3">
					<div class="form-check form-switch">
						<input class="form-check-input" type="checkbox" id="showOnlyMyRespond" />
						<label class="form-check-label fw-semibold" for="showOnlyMyRespond">
							<i class="bi bi-eye"></i> Show Only Reports I'm Responding To
						</label>
					</div>
				</li>
				<li class="mb-3">
					<label for="responderStatusSelect" class="form-label fw-semibold mb-1">
						<i class="bi bi-person-badge"></i> Status
					</label>
					<select class="form-select form-select-sm" id="responderStatusSelect">
						<option value="available" {{ Auth::user()->responderDetail?->status === 'available' ? 'selected' : '' }}>Available</option>
						<option value="busy" {{ Auth::user()->responderDetail?->status === 'busy' ? 'selected' : '' }}>Busy</option>
						<option value="offline" {{ Auth::user()->responderDetail?->status === 'offline' ? 'selected' : '' }}>Offline</option>
						<option value="unavailable" {{ Auth::user()->responderDetail?->status === 'unavailable' ? 'selected' : '' }}>Unavailable</option>
					</select>
				</li>
				<li>
					<div class="form-check form-switch">
						<input class="form-check-input" type="checkbox" id="autoAssignToggle" {{ Auth::user()->responderDetail?->auto_assign ? 'checked' : '' }} />
						<label class="form-check-label fw-semibold" for="autoAssignToggle">
							<i class="bi bi-gear"></i> Auto-Assign
						</label>
						<small class="form-text text-muted d-block mt-1">Automatically assign me to nearby reports</small>
					</div>
				</li>
				@endif
			</ul>
		</div>

		<div id="map-loader">
			<div class="loader-icon d-flex flex-column align-items-center">
				<img src="{{ asset('assets/img/map_search.gif') }}" alt="Map search" width="80" />
				<div class="loader-text">Finding routes...</div>
			</div>
		</div>
		<div id="map" class="shadow-sm"></div>
	</div>

	@if ($isValidatedUser && !$isValidatedResponder)
		{{-- ✅ Logged in, verified, and normal user --}}
		<div class="floating-icon"
			data-bs-toggle="modal"
			data-bs-target="#createReportModal"
			data-bs-placement="left"
			data-bs-title="Report Emergency"
			data-bs-toggle-tooltip>
			<i class="bi bi-plus"></i>
		</div>
	@elseif (!$isValidatedUser && !$isValidatedResponder)
		{{-- ⚠️ Logged in but email not verified --}}
		<a href="#" class="floating-icon"
			id="verifiedPrompt"
			data-bs-placement="left"
			data-bs-title="Please verify your email first"
			data-bs-toggle-tooltip>
			<i class="bi bi-plus"></i>
		</a>
	@elseif (!Auth::check())
		{{-- 🚪 Not logged in --}}
		<a href="#" class="floating-icon"
			id="loginPrompt"
			data-bs-placement="left"
			data-bs-title="Login to report an emergency"
			data-bs-toggle-tooltip>
			<i class="bi bi-plus"></i>
		</a>
	@endif


	<!-- Create Modal -->
	<div class="modal fade" id="createReportModal" tabindex="-1" aria-hidden="false">
		<div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
			<div class="modal-content">
				<div class="modal-header bg-success text-white">
					<h5 class="modal-title">Report Emergency Form</h5>
					<button type="button" class="btn border-0 bg-transparent" data-bs-dismiss="modal" aria-label="Close">
						<i class="bi bi-x-lg fs-12 text-white"></i>
					</button>
				</div>
				<div class="modal-body">
					<form id="reportForm" action="{{ route('report.store') }}" method="POST">
						@csrf

						<!-- Emergency Type -->
						<div class="mb-3">
							<div class="form-floating">
								<select name="type" id="type" class="form-select form-select-sm" required aria-label="Type of Emergency">
									<option value="">-- Select Type --</option>
									@foreach ($emergencyTypes as $type)
										<option value="{{ $type->code }}">
											{{ $type->name }} | {{ $type->description }}
										</option>
									@endforeach
								</select>
								<label for="type">Type of Emergency</label>
							</div>
						</div>

						<!-- Severity Level -->
						<div class="mb-3">
							<div class="form-floating">
								<select name="severity_level" id="severity_level" class="form-select form-select-sm" required aria-label="Severity Level">
									<option value="">-- Select Level --</option>
									<option value="low">Low</option>
									<option value="moderate">Moderate</option>
									<option value="high">High</option>
									<option value="critical">Critical</option>
								</select>
								<label for="severity_level">Severity Level</label>
							</div>
						</div>

						<!-- Description -->
						<div class="mb-3">
							<div class="form-floating">
								<textarea class="form-control form-control-sm" placeholder="Leave a description here" id="description" name="description" style="height: 100px"></textarea>
								<label for="description">Description</label>
							</div>
						</div>

						<!-- Contact Info -->
						<div class="form-floating mb-3">
							<input type="text" class="form-control form-control-sm" id="contact_name" name="contact_name" placeholder="Contact Name" required>
							<label for="contact_name">Contact Name</label>
						</div>

						<div class="form-floating mb-3">
							<input 
								type="tel" 
								class="form-control form-control-sm" 
								id="contact_number" 
								name="contact_number" 
								placeholder="09XXXXXXXXX"
								pattern="^09\d{9}$"
								maxlength="11"
								required
							>
							<label for="contact_number">Contact Number</label>
						</div>

						<!-- Map -->
						<div class="mb-3">
							<label for="map_create" class="form-label">Pin Your Location</label>
							<div id="map_create"></div>
							<small class="text-muted">
								Your current location will be set automatically. Click anywhere on the map to move the pin.
							</small>
						</div>

						<!-- Address -->
						<div class="form-floating mb-3">
							<input type="text" class="form-control form-control-sm" id="address" name="address" placeholder="Address (Auto-filled)">
							<label for="address" class="input_label">Address (Auto-filled)</label>
						</div>

						<!-- Media Upload -->
						<div class="mb-3">
							<label for="media_files" class="form-label">
								<i class="bi bi-images me-1"></i>Upload Media (Max 5 files) <span id="mediaCount" class="text-muted small"></span>
							</label>
							<input 
								type="file" 
								class="form-control form-control-sm" 
								id="media_files" 
								name="media_files[]" 
								multiple 
								accept="image/*,video/*"
							>
							<small class="text-muted">Supported: Images (Max 10MB) and Videos (Max 50MB) only. You can select files multiple times to add more.</small>
							<div id="mediaPreview" class="mt-2 row g-2"></div>
							<div id="mediaError" class="text-danger small mt-1"></div>
						</div>

						<!-- Hidden Inputs -->
						<input type="hidden" name="latitude" id="latitude">
					<input type="hidden" name="longitude" id="longitude">

					<button type="submit" class="btn btn-success btn-sm mt-3 btn_submit_report">Submit Report</button>
					</form>
				</div>
			</div>
		</div>
	</div>
	
	<!-- Report Modal -->
	<div class="modal fade" id="reportModal" tabindex="-1" aria-hidden="false">
		<div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
			<div class="modal-content">
				<div class="modal-header text-white">
					<h5 class="modal-title" id="reportModalTitle">Report Details</h5>
					<button type="button" class="btn border-0 bg-transparent" data-bs-dismiss="modal" aria-label="Close">
						<i class="bi bi-x-lg fs-12 text-white"></i>
					</button>
				</div>
				<div class="modal-body">
					<div class="row g-3">
						<div class="col-md-6">
							<div><strong>Type:</strong> <span id="modalType"></span></div>
							<div><strong>Severity:</strong> <span id="modalSeverity"></span></div>
							<div><strong>Status:</strong> <span id="modalStatus"></span></div>
							<div><strong>Contact:</strong> <span id="modalContact"></span></div>
							<div><strong>Address:</strong> <span id="modalAddress"></span></div>
							<div><strong>Description:</strong>
								<div id="modalDescription"></div>
							</div>
							<div><strong>Reported At:</strong> <span id="modalReportAt"></span></div>
						</div>
						<div class="col-md-6">
							<div id="miniMap"></div>
							<div class="mt-2 d-flex justify-content-between">
								<small class="text-muted">Route from your location (if allowed).</small>
								<a id="googleDir" href="#" target="_blank" class="btn btn-outline-primary btn-sm">
									Open in Google Maps
								</a>
							</div>
						</div>
						<div class="col-md-12">
							<div id="modalAllRespondersArea" style="display: none;">
								<hr>
								<div><strong>All Responders:</strong></div>
								<div id="modalAllResponders" class="mt-2"></div>
							</div>
						</div>
						<div class="col-md-12">
							<div id="modalMediaArea" style="display: none;">
								<hr>
								<div><strong><i class="bi bi-images me-1"></i>Attached Media:</strong></div>
								<div id="modalMedia" class="mt-2 row g-2"></div>
							</div>
						</div>
					</div>
				</div>
				<div class="modal-footer" id="reportModalFooter" style="display: none;">
					<div class="d-flex justify-content-between w-100">
						<div>
							<a id="editReportBtn" href="#" class="btn btn-primary btn-sm" style="display: none;">
								<i class="bi bi-eye me-1"></i> View Report Details
							</a>
							<button id="cancelReportBtn" type="button" class="btn btn-warning btn-sm" style="display: none;" onclick="cancelReportFromMap()">
								<i class="bi bi-x-circle me-1"></i> Cancel Report
							</button>
							<button id="deleteReportBtn" type="button" class="btn btn-danger btn-sm" style="display: none;" onclick="deleteReportFromMap()">
								<i class="bi bi-trash me-1"></i> Delete Report
							</button>
						</div>
						<div>
							@if(Auth::check() && Auth::user()->isResponder())
							<a id="respondBtn" href="#" class="btn btn-success btn-sm">Respond to Report</a>
							@endif
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

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
				<button id="prevMediaBtn" class="btn btn-light position-absolute top-50 start-0 translate-middle-y ms-3" style="z-index: 10; border-radius: 50%; width: 50px; height: 50px; display: none; box-shadow: 0 2px 10px rgba(0,0,0,0.3); background: rgba(255, 255, 255, 0.9);" title="Previous (←)" onclick="window.showPreviousMedia()">
					<i class="bi bi-chevron-left fs-5"></i>
				</button>
				<button id="nextMediaBtn" class="btn btn-light position-absolute top-50 end-0 translate-middle-y me-3" style="z-index: 10; border-radius: 50%; width: 50px; height: 50px; display: none; box-shadow: 0 2px 10px rgba(0,0,0,0.3); background: rgba(255, 255, 255, 0.9);" title="Next (→)" onclick="window.showNextMedia()">
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

	<script>
		const reports = @json($reports);
		const responders = @json($responders ?? []);
		const savedStyle = localStorage.getItem('selectedMapStyle');
		@if(Auth::check())
		window.currentUserId = {{ Auth::id() }};
		window.isResponder = {{ Auth::user()->isResponder() ? 'true' : 'false' }};
		window.isAdmin = {{ Auth::user()->isAdmin() ? 'true' : 'false' }};
		@if(Auth::user()->isResponder())
		const responderEmergencyTypes = @json(Auth::user()->responderDetail?->emergency_types ?? []);
		@else
		const responderEmergencyTypes = [];
		@endif
		@else
		window.currentUserId = null;
		window.isResponder = false;
		const responderEmergencyTypes = [];
		@endif
		const hideMyLocation = document.getElementById("hideMyLocation");
		const hideLegend = document.getElementById("hideLegend");
		const showOnlyMyReport = document.getElementById("showOnlyMyReport");
		const showOnlyMyRespond = document.getElementById("showOnlyMyRespond");

		hideMyLocation ? hideMyLocation.checked = localStorage.getItem("hideMyLocation") === "true" || false : null;
		hideLegend.checked = localStorage.getItem("hideLegend") === "true" || false;
		showOnlyMyReport ? showOnlyMyReport.checked = localStorage.getItem("showOnlyMyReport") === "true" || false : null;
		@if ($isValidatedResponder)
		showOnlyMyRespond.checked = localStorage.getItem("showOnlyMyRespond") === "true" || false;
		@endif

		// selectedStyle is already defined globally in script.js
		// Use it directly, or update if needed based on savedStyle
		let styleToUse = selectedStyle;
		if (typeof selectedStyle === 'undefined' || (savedStyle && selectedStyle.id !== savedStyle)) {
			const styleId = savedStyle || "openStreet_1";
			styleToUse = mapStyles.find(style => style.id === styleId) || mapStyles[0];
		}

		const map = L.map("map", {
			attributionControl: false,
			zoomControl: false,
			rotate: false,
			rotateControl: false
			// minZoom: 12,
		}).setView([10.3157, 123.8854], 12);

		let currentLayer = L.tileLayer(styleToUse.url, {
			attribution: styleToUse.attribution || '&copy; Emergency Response System',
		}).addTo(map);

		const legend = L.control({ position: "bottomleft" });
		legend.onAdd = function (map) {
			const div = L.DomUtil.create("div", "map-legend");
			div.innerHTML = `
				<h4 style="margin:0 0 5px 0;">Legend</h4>
				<h6 style="margin:0 0 5px 0;">Severity Levels (Color)</h6>
				<div><span class="critical legend-list"></span>Critical Severity</div>
				<div><span class="high legend-list"></span>High Severity</div>
				<div><span class="moderate legend-list"></span>Moderate Severity</div>
				<div><span class="low legend-list"></span>Low Severity</div>
				<h6 style="margin:5px 0 5px 0;">Icon</h6>
				<div><i class="bi bi-fire legend-list"></i>Fire</div>
				<div><i class="bi bi-car-front legend-list"></i>Road Accident</div>
				<div><i class="bi bi-shield-check legend-list"></i>Police Assistance</div>
				<div><i class="bi bi-heart-pulse legend-list"></i>Medical Emergency</div>
				<div><i class="bi bi-life-preserver legend-list"></i>Rescue</div>
				<div><i class="bi bi-droplet legend-list"></i>Flood</div>
				<div><i class="bi bi-building legend-list"></i>Earthquake</div>
				<div><i class="bi bi-exclamation-triangle legend-list"></i>Others</div>
				<div><img src="{{ asset('assets/img/ambulance.png') }}" class="legend-list"/>Responder</div>
				<div><img src="{{ asset('assets/img/person.png') }}" class="legend-list"/>User Location</div>
			`;
			return div;
		};

		legend.addTo(map)

		// Render all
		reports.forEach(addReportMarker);

		// Modal and mini-map
		let modalInstance, miniMap, miniMarker, routeControl, currentReport;

		if (document.getElementById("respondBtn")){
			document.getElementById("respondBtn").addEventListener("click", (e) => {
				if (!currentReport) {
					e.preventDefault();
					return false;
				}
				
				// Check if responder has emergency types selected
				@if(Auth::check() && Auth::user()->isResponder())
				if (typeof responderEmergencyTypes !== 'undefined') {
					// If no emergency types selected at all
					if (responderEmergencyTypes.length === 0) {
						e.preventDefault();
						e.stopPropagation();
						Swal.fire({
							icon: 'warning',
							title: 'No Emergency Types Selected',
							html: `You haven't selected any emergency types in your profile.<br><br>Please update your profile to select the emergency types you can respond to.`,
							confirmButtonText: 'Go to Profile',
							showCancelButton: true,
							cancelButtonText: 'Cancel',
							buttonsStyling: false,
							customClass: {
								confirmButton: 'btn btn-primary',
								cancelButton: 'btn btn-secondary'
							}
						}).then((result) => {
							if (result.isConfirmed) {
								window.location.href = '{{ route("profile.edit") }}';
							}
						});
						return false;
					}
					// If emergency types selected but not this specific type
					if (!responderEmergencyTypes.includes(currentReport.type)) {
						e.preventDefault();
						e.stopPropagation();
						Swal.fire({
							icon: 'error',
							title: 'Not Qualified',
							html: `You are not qualified to respond to <strong>${currentReport.type}</strong> emergencies.<br><br>Please update your profile to include this emergency type.`,
							confirmButtonText: 'Go to Profile',
							showCancelButton: true,
							cancelButtonText: 'Cancel',
							buttonsStyling: false,
							customClass: {
								confirmButton: 'btn btn-primary',
								cancelButton: 'btn btn-secondary'
							}
						}).then((result) => {
							if (result.isConfirmed) {
								window.location.href = '{{ route("profile.edit") }}';
							}
						});
						return false;
					}
				}
				@endif
			});
		}

		const coords = reports.filter(r => r.latitude && r.longitude).map(r => [r.latitude, r.longitude]);
		if (coords.length) map.fitBounds(coords, { padding: [40, 40] });

		document.addEventListener('DOMContentLoaded', function () {

			if (navigator.geolocation) {
				navigator.geolocation.getCurrentPosition(
					(pos) => {
						initMap(pos.coords.latitude, pos.coords.longitude);
					},
					() => {
						// fallback (Cebu City)
						initMap(10.3157, 123.8854);
						if (typeof Swal !== 'undefined') {
							Swal.fire({
								icon: 'info',
								title: 'Location Access',
								text: "Couldn't access your location — default location set.",
								toast: true,
								position: 'top-end',
								showConfirmButton: false,
								timer: 3000,
								timerProgressBar: true
							});
						}
					}
				);
			} else {
				initMap(10.3157, 123.8854);
				if (typeof Swal !== 'undefined') {
					Swal.fire({
						icon: 'info',
						title: 'Geolocation Not Supported',
						text: 'Geolocation is not supported by your browser.',
						toast: true,
						position: 'top-end',
						showConfirmButton: false,
						timer: 3000,
						timerProgressBar: true
					});
				}
			}

			// Clear media files when modal is closed
			$('#createReportModal').on('hidden.bs.modal', function() {
				$('#media_files').val('');
				$('#mediaPreview').empty();
				$('#mediaError').text('');
				$('#mediaCount').text('');
				selectedFiles = [];
			});

			$('#createReportModal').on('shown.bs.modal', () => {
				if (mapCreate) {
					mapCreate.invalidateSize();
				}
			});

			// Auto-open modal if new_report=1 is in URL
			const urlParams = new URLSearchParams(window.location.search);
			if (urlParams.get('new_report') === '1') {
				const createReportModal = new bootstrap.Modal(document.getElementById('createReportModal'));
				createReportModal.show();
				
				// Remove the parameter from URL without reload
				urlParams.delete('new_report');
				const newUrl = window.location.pathname + (urlParams.toString() ? '?' + urlParams.toString() : '');
				window.history.replaceState({}, '', newUrl);
			}

			if (hideMyLocation && !hideMyLocation.checked) {
				displayUserLocation();
			}

			if (hideLegend.checked) {
				$('.map-legend').hide();
			} else {
				$('.map-legend').show();
			}

			if (showOnlyMyReport && showOnlyMyReport.checked) {
				setTimeout(() => {
					showReportsByUser({{ Auth::id() ?? 'null' }});
				}, 0);
			} else {
				resetReportMarkers();
			}

			@if ($isValidatedResponder)
				if (showOnlyMyRespond.checked) {
					setTimeout(() => {
						showReportsByResponder({{ Auth::id() ?? 'null' }});
					}, 0);
				} else {
					resetReportMarkers();
				}
			@endif

			const responderLocation = pusher.subscribe('responder_location');
			let routeControl = null;

			// Display all responders for each report
			if (responders && typeof responders === 'object') {
				Object.keys(responders).forEach(reportId => {
					const responderList = responders[reportId]; // This is an array
					
					if (Array.isArray(responderList)) {
						// Loop through each responder in that report
						responderList.forEach(responder => {
							// Only display if responder has valid coordinates and status is 'en_route' or 'on_scene'
							if (responder && responder.latitude && responder.longitude) {
								const status = responder.responder_status;
								if (status && ['en_route', 'on_scene'].includes(status)) {
									displayResponders(responder);
								}
							}
						});
					}
				});
			} else {
				console.warn('Responders data is not in expected format:', typeof responders);
			}

			responderLocation.bind('message.responder_location', function (res) {
				displayResponders(res.data)
			});

			
			const select = document.getElementById('mapStyleSelect');
			mapStyles.forEach(style => {
				const option = document.createElement('option');
				option.value = style.id;
				option.textContent = style.name;
				select.appendChild(option);
			});


			if (savedStyle) {
				select.value = savedStyle;
			}

			// ✅ Save new selection when user changes it
			select.addEventListener('change', () => {
				const selectedValue = select.value;
				localStorage.setItem('selectedMapStyle', selectedValue);

				const newStyle = mapStyles.find(s => s.id === selectedValue);
				if (newStyle) {
					map.removeLayer(currentLayer);
					mapCreate.removeLayer(currentLayer);
					if (miniMap) {
						miniMap.removeLayer(currentLayer);
					}
					
					L.tileLayer(newStyle.url, { attribution: newStyle.attribution }).addTo(mapCreate);
					if (miniMap) {
						L.tileLayer(newStyle.url, { attribution: newStyle.attribution }).addTo(miniMap);
					}
					currentLayer = L.tileLayer(newStyle.url, { attribution: newStyle.attribution }).addTo(map);
					
				}
			});

			hideMyLocation && hideMyLocation.addEventListener("change", () => {
				localStorage.setItem("hideMyLocation", hideMyLocation.checked);
				if (hideMyLocation.checked && navigator.geolocation) {
					hideUserLocation();
				} else if (!hideMyLocation.checked && navigator.geolocation) {
					displayUserLocation();
				}
			});

			hideLegend.addEventListener("change", () => {
				localStorage.setItem("hideLegend", hideLegend.checked);
				if (hideLegend.checked) {
					$('.map-legend').hide();
				} else {
					$('.map-legend').show();
				}
			});

			showOnlyMyReport && showOnlyMyReport.addEventListener("change", () => {
				localStorage.setItem("showOnlyMyReport", showOnlyMyReport.checked);
				if (showOnlyMyReport.checked) {
					showReportsByUser({{ Auth::id() ?? 'null' }});
				} else {
					resetReportMarkers();
				}
			});

			@if ($isValidatedResponder)
			showOnlyMyRespond.addEventListener("change", () => {
				localStorage.setItem("showOnlyMyRespond", showOnlyMyRespond.checked);
				if (showOnlyMyRespond.checked) {
					showReportsByResponder({{ Auth::id() ?? 'null' }});
				} else {
					resetReportMarkers();
				}
			});

			// Handle responder status change
			const responderStatusSelect = document.getElementById('responderStatusSelect');
			if (responderStatusSelect) {
				responderStatusSelect.addEventListener('change', function() {
					const status = this.value;
					const previousStatus = '{{ Auth::user()->responderDetail?->status ?? "available" }}';
					
					// Show loading
					if (typeof Swal !== 'undefined') {
						Swal.fire({
							title: 'Updating Status...',
							allowOutsideClick: false,
							allowEscapeKey: false,
							showConfirmButton: false,
							didOpen: () => {
								Swal.showLoading();
							}
						});
					}

					// Update status via AJAX
					fetch('{{ route("profile.responder-settings") }}', {
						method: 'POST',
						headers: {
							'Content-Type': 'application/json',
							'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
							'Accept': 'application/json'
						},
						body: JSON.stringify({ status: status })
					})
					.then(response => response.json())
					.then(data => {
						Swal.close();
						if (data.status === 'success') {
							if (typeof Swal !== 'undefined') {
								Swal.fire({
									icon: 'success',
									title: 'Status Updated!',
									text: 'Your status has been updated successfully.',
									toast: true,
									position: 'top-end',
									showConfirmButton: false,
									timer: 2000,
									timerProgressBar: true
								});
							}
						}
					})
					.catch(error => {
						Swal.close();
						if (typeof Swal !== 'undefined') {
							Swal.fire({
								icon: 'error',
								title: 'Error',
								text: 'Failed to update status. Please try again.',
								toast: true,
								position: 'top-end',
								showConfirmButton: false,
								timer: 3000,
								timerProgressBar: true
							});
						}
						// Revert to previous value on error
						this.value = previousStatus;
					});
				});
			}

			// Handle auto-assign toggle
			const autoAssignToggle = document.getElementById('autoAssignToggle');
			if (autoAssignToggle) {
				autoAssignToggle.addEventListener('change', function() {
					const autoAssign = this.checked;
					const previousValue = !autoAssign; // Store previous value
					
					// Show loading
					if (typeof Swal !== 'undefined') {
						Swal.fire({
							title: 'Updating Settings...',
							allowOutsideClick: false,
							allowEscapeKey: false,
							showConfirmButton: false,
							didOpen: () => {
								Swal.showLoading();
							}
						});
					}

					// Update auto-assign via AJAX
					fetch('{{ route("profile.responder-settings") }}', {
						method: 'POST',
						headers: {
							'Content-Type': 'application/json',
							'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
							'Accept': 'application/json'
						},
						body: JSON.stringify({ auto_assign: autoAssign })
					})
					.then(response => response.json())
					.then(data => {
						Swal.close();
						if (data.status === 'success') {
							if (typeof Swal !== 'undefined') {
								Swal.fire({
									icon: 'success',
									title: autoAssign ? 'Auto-Assign Enabled' : 'Auto-Assign Disabled',
									text: autoAssign 
										? 'You will be automatically assigned to nearby reports.' 
										: 'Auto-assignment has been disabled.',
									toast: true,
									position: 'top-end',
									showConfirmButton: false,
									timer: 2000,
									timerProgressBar: true
								});
							}
						}
					})
					.catch(error => {
						Swal.close();
						if (typeof Swal !== 'undefined') {
							Swal.fire({
								icon: 'error',
								title: 'Error',
								text: 'Failed to update auto-assign setting. Please try again.',
								toast: true,
								position: 'top-end',
								showConfirmButton: false,
								timer: 3000,
								timerProgressBar: true
							});
						}
						// Revert toggle on error
						this.checked = previousValue;
					});
				});
			}
			@endif

			const loginBtn = document.getElementById('loginPrompt');
			if (loginBtn) {
				loginBtn.addEventListener('click', function (e) {
					e.preventDefault();

					Swal.fire({
						title: 'Login Required',
						text: 'You must be logged in to submit an emergency report.',
						icon: 'warning',
						confirmButtonText: 'Login',
						buttonsStyling: false,
						customClass: {
							confirmButton: 'btn btn-success'
						},
						preConfirm: () => {
							window.location.href = "{{ route('login') }}";
							return false;
						}
					});
				});
			}

			const verifiedPrompt = document.getElementById('verifiedPrompt');
			if (verifiedPrompt) {
				verifiedPrompt.addEventListener('click', function (e) {
					e.preventDefault();

					Swal.fire({
						title: 'Email Verification Required',
						text: 'Please verify your email address to submit an emergency report.',
						icon: 'warning',
						confirmButtonText: 'Resend Verification Email',
						buttonsStyling: false,
						customClass: {
							confirmButton: 'btn btn-success'
						},
					}).then((result) => {
						if (result.isConfirmed) {
							$.ajax({
								url: "{{ route('verification.send') }}",
								method: 'POST',
								headers: {
									'X-CSRF-TOKEN': '{{ csrf_token() }}'
								},
								beforeSend: function() {
									Swal.fire({
										title: 'Sending...',
										text: 'Please wait while we resend the verification email.',
										allowOutsideClick: false,
										didOpen: () => {
											Swal.showLoading();
										}
									});
								},
								success: function(response) {
									Swal.fire({
										icon: 'success',
										title: 'Sent!',
										text: 'Verification email has been resent.',
										buttonsStyling: false,
										customClass: {
											confirmButton: 'btn btn-success'
										}
									})
										.then(() => {
											location.reload(); // refresh page
										});
								},
								error: function(xhr, status, error) {
									Swal.fire({
										icon: 'error',
										title: 'Error',
										text: 'Unable to send verification email.',
										buttonsStyling: false,
										customClass: {
											confirmButton: 'btn btn-danger'
										}
									});
								}
							});
						}
					});
				});
			}


			const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle-tooltip]');
			[...tooltipTriggerList].forEach(el => {
				new bootstrap.Tooltip(el);
			});
		});

		$(document).ready(function() {
			@if($emailVerifiedAt)
				const urlParams = new URLSearchParams(window.location.search);
				const verified = urlParams.get('verified');
				if (verified === '1' && !localStorage.getItem('verifiedAlertShown')) {
					const verifiedAt = new Date("{{ $emailVerifiedAt }}");
					const now = new Date();

					const diffMinutes = (now - verifiedAt) / 1000 / 60;

					if (diffMinutes <= 0.2) {
						Swal.fire({
							icon: 'success',
							title: 'Email Verified!',
							text: 'Your email has been successfully verified.',
							confirmButtonText: 'OK',
							buttonsStyling: false,
							customClass: {
								confirmButton: 'btn btn-success'
							}
						}).then(() => {
							localStorage.setItem('verifiedAlertShown', 'true');

							const url = new URL(window.location);
							url.searchParams.delete('verified');
							window.history.replaceState({}, document.title, url.toString());
						});
					}
				}
			@endif

			// Media upload preview and validation
			let selectedFiles = [];
			
			function updatePreview() {
				const maxFiles = 5;
				const maxSize = 10 * 1024 * 1024; // 10MB
				const preview = $('#mediaPreview');
				const errorDiv = $('#mediaError');
				
				errorDiv.text('');
				preview.empty();
				
				// Check total file count
				if (selectedFiles.length > maxFiles) {
					errorDiv.text(`Maximum ${maxFiles} files allowed. Please remove some files.`);
					selectedFiles = selectedFiles.slice(0, maxFiles);
				}
				
				// Display all selected files
				selectedFiles.forEach((file, index) => {
					// Check file size
					if (file.size > maxSize) {
						errorDiv.text(errorDiv.text() + `\n${file.name} is too large (max 10MB).`);
					}
					
					// Create preview
					const col = $('<div>').addClass('col-6 col-md-4');
					const card = $('<div>').addClass('card border');
					
					if (file.type.startsWith('image/')) {
						const img = $('<img>')
							.attr('src', URL.createObjectURL(file))
							.addClass('card-img-top')
							.css({'height': '80px', 'object-fit': 'cover'});
						card.append(img);
					} else if (file.type.startsWith('video/')) {
						const video = $('<video>')
							.attr('src', URL.createObjectURL(file))
							.addClass('card-img-top')
							.css({'height': '80px', 'object-fit': 'cover'})
							.prop('controls', false)
							.prop('muted', true);
						card.append(video);
						// Add play icon overlay
						const overlay = $('<div>')
							.addClass('position-absolute top-50 start-50 translate-middle')
							.html('<i class="bi bi-play-circle-fill text-white" style="font-size: 2rem; text-shadow: 0 0 5px rgba(0,0,0,0.5);"></i>');
						card.css('position', 'relative').append(overlay);
					}
					
					const removeBtn = $('<button>')
						.addClass('btn btn-sm btn-danger w-100')
						.html('<i class="bi bi-x"></i> Remove')
						.on('click', function() {
							removeFile(index);
						});
					
					card.append($('<div>').addClass('card-body p-1').append(removeBtn));
					col.append(card);
					preview.append(col);
				});
				
				// Update file count display
				if (selectedFiles.length > 0) {
					$('#mediaCount').text(`(${selectedFiles.length}/${maxFiles} files selected)`);
				} else {
					$('#mediaCount').text('');
				}
				
				// Update file input
				const input = document.getElementById('media_files');
				const dt = new DataTransfer();
				selectedFiles.forEach(file => dt.items.add(file));
				input.files = dt.files;
			}
			
			$('#media_files').on('change', function(e) {
				const newFiles = Array.from(e.target.files);
				const maxFiles = 5;
				const maxSize = 10 * 1024 * 1024; // 10MB
				const errorDiv = $('#mediaError');
				let errorMessages = [];
				
				// Add new files to existing selection
				newFiles.forEach((file) => {
					// Check if we've reached max files
					if (selectedFiles.length >= maxFiles) {
						errorMessages.push(`Maximum ${maxFiles} files allowed. Cannot add more files.`);
						return;
					}
					
					// Check file type - only images and videos
					const isImage = file.type.startsWith('image/');
					const isVideo = file.type.startsWith('video/');
					
					if (!isImage && !isVideo) {
						errorMessages.push(`${file.name} is not a valid image or video file.`);
						return;
					}
					
					// Check file size - different limits for images and videos
					const videoMaxSize = 50 * 1024 * 1024; // 50MB for videos
					const imageMaxSize = 10 * 1024 * 1024; // 10MB for images
					const allowedSize = isVideo ? videoMaxSize : imageMaxSize;
					
					if (file.size > allowedSize) {
						const maxSizeMB = isVideo ? 50 : 10;
						errorMessages.push(`${file.name} is too large (max ${maxSizeMB}MB for ${isVideo ? 'videos' : 'images'}).`);
						return;
					}
					
					// Check if file already exists (by name and size)
					const exists = selectedFiles.some(f => f.name === file.name && f.size === file.size);
					if (!exists) {
						selectedFiles.push(file);
					}
				});
				
				// Display errors
				if (errorMessages.length > 0) {
					errorDiv.html(errorMessages.join('<br>'));
				} else {
					errorDiv.text('');
				}
				
				// Update preview
				updatePreview();
				
				// Clear the input so user can select more files
				$(this).val('');
			});
			
			function removeFile(index) {
				selectedFiles.splice(index, 1);
				updatePreview();
			}

			$('#reportForm').on('submit', function(e) {
				e.preventDefault();

				let form = $(this);
				let submitBtn = $('.btn_submit_report');

				submitBtn.prop('disabled', true).text('Submitting...');

				// Create FormData for file upload
				let formData = new FormData();
				
				// Add form fields
				form.serializeArray().forEach(function(item) {
					if (item.name !== 'media_files[]') {
						formData.append(item.name, item.value);
					}
				});
				
				// Add CSRF token
				formData.append('_token', $('meta[name="csrf-token"]').attr('content'));

				$.ajax({
					url: form.attr('action'),
					method: 'POST',
					data: formData,
					processData: false,
					contentType: false,
					headers: {
						'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
					},
					success: function(response) {
						// Upload media files if report was created successfully
						if (response.report && response.report.id && selectedFiles.length > 0) {
							let mediaFormData = new FormData();
							selectedFiles.forEach((file, index) => {
								mediaFormData.append(`files[${index}]`, file);
							});
							
							$.ajax({
								url: `/reports/${response.report.id}/media`,
								method: 'POST',
								data: mediaFormData,
								processData: false,
								contentType: false,
								headers: {
									'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
								},
								success: function(mediaResponse) {
									// Show success message with media info
									if (mediaResponse.status === 'success') {
										console.log(`Successfully uploaded ${mediaResponse.media.length} file(s)`);
									} else {
										console.warn('Media upload response:', mediaResponse);
									}
								},
								error: function(xhr) {
									console.error('Error uploading media:', xhr);
									let errorMessage = 'Failed to upload media files.';
									
									if (xhr.responseJSON) {
										if (xhr.responseJSON.error) {
											const error = xhr.responseJSON.error;
											if (typeof error === 'string') {
												errorMessage = error;
											} else if (typeof error === 'object') {
												errorMessage = JSON.stringify(error);
											}
										} else if (xhr.responseJSON.message) {
											errorMessage = xhr.responseJSON.message;
										}
									} else if (xhr.responseText) {
										try {
											const parsed = JSON.parse(xhr.responseText);
											errorMessage = parsed.error || parsed.message || errorMessage;
										} catch (e) {
											errorMessage = xhr.responseText.substring(0, 200);
										}
									}
									
									console.error('Media upload error details:', errorMessage);
									
									// Show error to user
									Swal.fire({
										icon: 'warning',
										title: 'Media Upload Warning',
										text: errorMessage,
										confirmButtonText: 'OK'
									});
								}
							});
						}
						
						// Store media upload status
						let mediaUploadPending = selectedFiles.length > 0;
						
						submitBtn.prop('disabled', false).text('Submit Report');
						
						// Show success message
						let successMessage = response.message || 'Emergency report has been successfully sent.';
						if (mediaUploadPending) {
							successMessage += ' Uploading media files...';
						}
						
						Swal.fire({
							icon: 'success',
							title: 'Report Submitted',
							text: successMessage,
							buttonsStyling: false,
							customClass: {
								confirmButton: 'btn btn-success'
							},
							timer: mediaUploadPending ? 3000 : 2500,
							timerProgressBar: true,
						});
						
						// Reset form and clear media
						form.trigger('reset');
						$('#mediaPreview').empty();
						$('#mediaError').text('');
						$('#mediaCount').text('');
						selectedFiles = [];
						$('#createReportModal').modal('hide');
						
						// Update map
						addReportMarker(response.report);
						updateReportData(response.report);
					},
					error: function(xhr) {
						submitBtn.prop('disabled', false).text('Submit Report');

						if (xhr.status === 422) {
							let errors = xhr.responseJSON.errors;
							let messages = Object.values(errors).flat().join('<br>');

							Swal.fire({
								icon: 'error',
								title: 'Validation Error',
								html: messages,
							buttonsStyling: false,
							customClass: {
								confirmButton: 'btn btn-danger'
							}
							});
						} else {
							Swal.fire({
								icon: 'error',
								title: 'Submission Failed',
								text: xhr.responseJSON?.message || 'Something went wrong. Please try again.',
							buttonsStyling: false,
							customClass: {
								confirmButton: 'btn btn-danger'
							}
							});
						}
					}
				});
			});
		});

	@if (session('error') && session('pending_url'))
        document.addEventListener("DOMContentLoaded", function() {
            Swal.fire({
                icon: 'warning',
                title: 'Notice',
                text: "{{ session('error') }}",
                showCancelButton: true,
                confirmButtonText: 'Go to pending response',
                cancelButtonText: 'Close',
                buttonsStyling: false,
                customClass: {
                    confirmButton: 'btn btn-success',
                    cancelButton: 'btn btn-secondary'
                },
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = "{{ session('pending_url') }}";
                }
            });
        });
	@elseif (session('error'))
		document.addEventListener("DOMContentLoaded", function() {
			Swal.fire({
				icon: 'error',
				title: "{{ session('error_title') }}",
				text: "{{ session('error') }}",
				confirmButtonText: 'Go to Profile',
				showCancelButton: true,
				cancelButtonText: 'Close',
				buttonsStyling: false,
				customClass: {
					confirmButton: 'btn btn-primary',
					cancelButton: 'btn btn-secondary'
				},
			}).then((result) => {
				if (result.isConfirmed) {
					window.location.href = "{{ route('profile.edit') }}";
				}
			});
		});
	@endif
	</script>
@endsection