let mapStyles = [
	{ id: "openStreet_1", name: "OpenStreetMap Standard", url: "https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", attribution: "&copy; OpenStreetMap contributors" },
	{ id: "openStreet_2", name: "OpenStreetMap HOT", url: "https://{s}.tile.openstreetmap.fr/hot/{z}/{x}/{y}.png", attribution: "&copy; OpenStreetMap contributors" },
	{ id: "stadia_alidade_smooth", name: "Stadia Alidade Smooth", url: "https://tiles.stadiamaps.com/tiles/alidade_smooth/{z}/{x}/{y}.png", attribution: "&copy; Stadia Maps & OpenStreetMap" },
	{ id: "osm_de", name: "OpenStreetMap Germany", url: "https://{s}.tile.openstreetmap.de/tiles/osmde/{z}/{x}/{y}.png", attribution: "&copy; OpenStreetMap contributors" },
	{ id: "cartoLight", name: "Carto Light", url: "https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png", attribution: "&copy; OpenStreetMap &copy; CARTO" },
	{ id: "cartoDark", name: "Carto Dark", url: "https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png", attribution: "&copy; OpenStreetMap &copy; CARTO" },
	{ id: "cartoVoyager", name: "Carto Voyager", url: "https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png", attribution: "&copy; OpenStreetMap &copy; CARTO" },
	{ id: "esriStreet", name: "ESRI Street Map", url: "https://server.arcgisonline.com/ArcGIS/rest/services/World_Street_Map/MapServer/tile/{z}/{y}/{x}", attribution: "Tiles &copy; Esri" },
	{ id: "esriImagery", name: "ESRI Imagery", url: "https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}", attribution: "Tiles &copy; Esri" },
	{ id: "esriTopo", name: "ESRI Topographic", url: "https://server.arcgisonline.com/ArcGIS/rest/services/World_Topo_Map/MapServer/tile/{z}/{y}/{x}", attribution: "Tiles &copy; Esri" },
	{ id: "openTopoMap", name: "OpenTopoMap", url: "https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png", attribution: "&copy; OpenStreetMap contributors, &copy; OpenTopoMap" },
	{ id: "cyclosm", name: "CyclOSM", url: "https://{s}.tile-cyclosm.openstreetmap.fr/cyclosm/{z}/{x}/{y}.png", attribution: "&copy; OpenStreetMap contributors, &copy; CyclOSM" },
];


const savedStyleId = localStorage.getItem("selectedMapStyle") || "default_1";
const selectedStyle = mapStyles.find(style => style.id === savedStyleId) || mapStyles[0];

$(window).on('load', function () {
	$('.preloader').fadeOut(100);
	$("#map-loader").hide();

	// update every second
	setInterval(updateDateTime, 1000);
	updateDateTime();
});

function updateReportData(data) {
	const index = reports.findIndex(report => report.id === data.id);

	// Handle deletion
	if (data.deleted === true || data.action === 'delete') {
		if (index !== -1) {
			reports.splice(index, 1);
		}
		return;
	}

	if (index === -1) {
		reports.push(data);
	} else {
		reports.splice(index, 1);
		reports.push(data);
	}
}

function updateDateTime() {
	const now = new Date();

	const options = {
		weekday: 'short',
		month: 'short',
		day: 'numeric',
		year: 'numeric',
		hour: '2-digit',
		minute: '2-digit',
		second: '2-digit'
	};

	const formatted = now.toLocaleString('en-PH', options);
	document.getElementById('time_and_date').textContent = formatted;
}

const reportMarkers = {};
const responderMarkers = {}; // store all responders by id

let mapCreate;
let marker;

function createCustomMarker(type, severity) {
	const iconClass = typeIcons[type] || typeIcons.others;
	const colorClass = `${severity}-pin`;
	const html = `<div class="pin-icon ${colorClass}"><i class="bi ${iconClass}"></i></div>`;
	return L.divIcon({ className: "", html, iconSize: [42, 42], iconAnchor: [15.5, 36] });
}

function addReportMarker(report) {
	if (reportMarkers[report.id]) return;

	if (!report.latitude || !report.longitude) return;

	const severity = (report.severity_level || "moderate").toLowerCase();
	const type = (report.type || "others").toLowerCase();

	const pulse = L.marker([report.latitude, report.longitude], {
		icon: L.divIcon({
			className: "",
			html: `<div class="pulse ${severity}"></div>`,
			iconSize: [55, 55],
			iconAnchor: [27.5, 45],
		}),
		interactive: false,
		zIndexOffset: -100,
	}).addTo(map);

	const marker = L.marker([report.latitude, report.longitude], {
		icon: createCustomMarker(type, severity),
	}).addTo(map);

	map.setView([report.latitude, report.longitude], 14);

	// 🟡 Add tooltip content
	const tooltipContent = `
		<strong>${report.title || "Emergency Report"}</strong><br>
		Type: ${report.type || "Unknown"}<br>
		Severity: ${report.severity_level || "Moderate"}<br>
		${report.location ? `Location: ${report.location}` : ""}
	`;

	// 🟢 Bind tooltip
	marker.bindTooltip(tooltipContent, {
		permanent: false,   // show on hover or tap
		direction: 'bottom', // display above the marker
		offset: [3, 1],   // small upward offset
	});

	marker.on("click", () => openReportModal(report));
	map.on("zoom", () => pulse.setLatLng(marker.getLatLng()));

	reportMarkers[report.id] = { marker, pulse, report };
}

function openReportModal(report) {
	removeAllRoutes();

	currentReport = report;

	const respondBtn = document.getElementById("respondBtn");
	const editReportBtn = document.getElementById("editReportBtn");
	const cancelReportBtn = document.getElementById("cancelReportBtn");
	const deleteReportBtn = document.getElementById("deleteReportBtn");
	let userId = typeof currentUserId !== 'undefined' ? currentUserId : (typeof window.currentUserId !== 'undefined' ? window.currentUserId : null);
	const userIsResponder = typeof isResponder !== 'undefined' ? isResponder : (typeof window.isResponder !== 'undefined' ? window.isResponder : false);
	const userIsAdmin = typeof isAdmin !== 'undefined' ? isAdmin : (typeof window.isAdmin !== 'undefined' ? window.isAdmin : false);
	
	// Show/hide edit button if user owns the report
	if (editReportBtn) {
		if (userId && report.user_id && parseInt(report.user_id) === parseInt(userId)) {
			editReportBtn.href = `/reports/${report.id}`;
			editReportBtn.style.display = 'inline-block';
		} else {
			editReportBtn.style.display = 'none';
		}
	}
	
	// Show/hide cancel button if user owns the report OR is admin/super admin (and report is not already cancelled/resolved)
	if (cancelReportBtn) {
		const canCancel = ((userId && report.user_id && parseInt(report.user_id) === parseInt(userId)) || userIsAdmin) && 
		                  !report.is_cancelled && report.status !== 'cancelled' && report.status !== 'resolved';
		if (canCancel) {
			cancelReportBtn.style.display = 'inline-block';
			cancelReportBtn.setAttribute('data-report-id', report.id);
		} else {
			cancelReportBtn.style.display = 'none';
		}
	}
	
	// Show/hide delete button if user owns the report OR is admin/super admin
	if (deleteReportBtn) {
		const canDelete = (userId && report.user_id && parseInt(report.user_id) === parseInt(userId)) || userIsAdmin;
		if (canDelete) {
			deleteReportBtn.style.display = 'inline-block';
			deleteReportBtn.setAttribute('data-report-id', report.id);
			// Check if responders are assigned and disable button if needed
			checkRespondersForDeleteButton(report.id, deleteReportBtn);
		} else {
			deleteReportBtn.style.display = 'none';
		}
	}
	
	if (respondBtn) {
		// Hide respond button if report is cancelled
		if (report.is_cancelled === true || report.is_cancelled === 1) {
			respondBtn.style.display = 'none';
			return;
		}

		// Check if responder has the emergency type (if responderEmergencyTypes is defined)
		let canRespond = true;
		let hasNoTypes = false;
		if (typeof responderEmergencyTypes !== 'undefined') {
			if (responderEmergencyTypes.length === 0) {
				hasNoTypes = true;
				canRespond = false;
			} else {
				canRespond = responderEmergencyTypes.includes(report.type);
			}
		}
		
		// Check if user is assigned as responder (primary or secondary)
		checkIfSecondaryResponder(report.id, userId, function(isAssigned, isPrimary, isCompleted) {
			if (isAssigned && !isCompleted) {
				respondBtn.textContent = isPrimary ? "Continue Response" : "Continue as Secondary Responder";
				respondBtn.classList.remove('btn-secondary');
				respondBtn.classList.add(isPrimary ? 'btn-success' : 'btn-info');
				respondBtn.disabled = false;
				respondBtn.href = `/response/${report.id}`;
				respondBtn.onclick = null;
				$('#reportModalFooter').show();
			} else {

				if (isCompleted) {
					// goto the response page
					respondBtn.textContent = "Go to report details";
					respondBtn.href = `/reports/${report.id}`;
					respondBtn.disabled = false;
					$('#reportModalFooter').show();
					return;
				}

				// Check if there's a primary responder
				checkIfHasPrimaryResponder(report.id, function(hasPrimary) {
					if (hasPrimary) {
						// Primary responder exists, check if current user can join as secondary
						if (userIsResponder && canRespond) {
							respondBtn.textContent = "Join as Secondary Responder";
							respondBtn.classList.remove('btn-secondary');
							respondBtn.classList.add('btn-info');
							respondBtn.href = '#';
							respondBtn.onclick = function(e) {
								e.preventDefault();
								joinAsSecondaryResponder(report.id, userId);
							};
							respondBtn.disabled = false;
							$('#reportModalFooter').show();
						} else {
							// Check if edit, cancel, or delete button should be shown
							if ((editReportBtn && editReportBtn.style.display !== 'none') || 
							    (cancelReportBtn && cancelReportBtn.style.display !== 'none') ||
							    (deleteReportBtn && deleteReportBtn.style.display !== 'none')) {
								$('#reportModalFooter').show();
							} else {
								$('#reportModalFooter').hide();
							}
						}
					} else {
						// No primary responder, show normal respond button
						respondBtn.textContent = "Respond to Report";
						if (canRespond) {
							respondBtn.classList.remove('btn-secondary');
							respondBtn.classList.add('btn-success');
							respondBtn.disabled = false;
							respondBtn.title = "";
						} else {
							respondBtn.classList.remove('btn-success');
							respondBtn.classList.add('btn-secondary');
							respondBtn.disabled = false; // Keep enabled so click handler can show SweetAlert
							if (hasNoTypes) {
								respondBtn.title = "Please select emergency types in your profile first";
							} else {
								respondBtn.title = "You are not qualified to respond to this type of emergency";
							}
						}
						respondBtn.href = `/response/${report.id}`;
						respondBtn.onclick = null;
						$('#reportModalFooter').show();
					}
				});
			}
		});
	} else {
		// If respondBtn doesn't exist but edit, cancel, or delete button should be shown, show footer
		if ((editReportBtn && editReportBtn.style.display !== 'none') || 
		    (cancelReportBtn && cancelReportBtn.style.display !== 'none') ||
		    (deleteReportBtn && deleteReportBtn.style.display !== 'none')) {
			$('#reportModalFooter').show();
		}
	}

	document.getElementById("reportModalTitle").textContent = `Report #${report.id ?? ""} — ${report.type ?? ""}`;
	document.getElementById("modalType").textContent = report.type ?? "";
	document.getElementById("modalSeverity").textContent = report.severity_level ?? "";
	document.getElementById("modalStatus").textContent = report.status ?? "";
	document.getElementById("modalContact").textContent = `${report.contact_name ?? "N/A"} (${report.contact_number ?? "N/A"})`;
	document.getElementById("modalAddress").textContent = report.address ?? "Unknown";
	document.getElementById("modalDescription").innerHTML =(report.description ?? "No description").replace(/\n/g, "<br>");
	// Format date as "Nov 26, 2025 4:29 PM"
	const reportDate = new Date(report.created_at);
	const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
	const month = monthNames[reportDate.getMonth()];
	const day = reportDate.getDate();
	const year = reportDate.getFullYear();
	
	// Format time with AM/PM
	let hours = reportDate.getHours();
	const minutes = reportDate.getMinutes().toString().padStart(2, '0');
	const ampm = hours >= 12 ? 'PM' : 'AM';
	hours = hours % 12;
	hours = hours ? hours : 12; // 0 should be 12
	const timeStr = `${hours}:${minutes} ${ampm}`;
	
	const formattedDate = `${month} ${day}, ${year} ${timeStr}`;
	document.getElementById("modalReportAt").innerHTML = formattedDate;
	
	// Check if report has any responders assigned
	// userId is already declared above
	
	// Load all responders to check if any exist
	loadAllResponders(report.id, function(hasResponders, primaryResponder) {
		// Check if report is cancelled (primary responder status is 'cancelled')
		const isCancelled = primaryResponder && primaryResponder.status === 'cancelled';
		if (!hasResponders || isCancelled) {
			$('#modalAllRespondersArea').hide();
		} else {
			$('#modalAllRespondersArea').show();
		}
	});

	// Load media for this report
	loadReportMedia(report.id);

	document.getElementById("googleDir").href = `https://www.google.com/maps/dir/?api=1&destination=${report.latitude},${report.longitude}`;

	// Function to load all responders for a report
	function loadAllResponders(reportId, callback) {
		fetch(`/reports/${reportId}/responders`)
			.then(response => response.json())
			.then(data => {
				if (data.status === 'success' && data.responders && data.responders.length > 0) {
					const container = document.getElementById('modalAllResponders');
					if (container) {
						container.innerHTML = '<div class="row"></div>';
						const row = container.querySelector('.row');
						
						const userId = typeof currentUserId !== 'undefined' ? currentUserId : (typeof window.currentUserId !== 'undefined' ? window.currentUserId : null);
						let primaryResponder = null;
						
						data.responders.forEach(assignment => {
							if (assignment.role === 'primary') {
								primaryResponder = assignment;
							}
							
							if (row) {
								const colDiv = document.createElement('div');
								colDiv.className = 'col-6 mb-3';
								
								const responderDiv = document.createElement('div');
								responderDiv.className = 'h-100 p-3 border rounded';
								
								const isCurrentUser = assignment.responder_id === userId;
								const roleBadge = assignment.role === 'primary' 
									? '<span class="badge bg-primary">Primary</span>' 
									: '<span class="badge bg-secondary">Secondary</span>';
								const statusBadge = getStatusBadge(assignment.status);
								
								// Build responder info
								let responderInfo = `
									<div class="d-flex justify-content-between align-items-start mb-2">
										<div>
											<strong>${assignment.responder?.name || 'Unknown'}</strong> ${isCurrentUser ? '(You)' : ''}
											<br>
											<small class="text-muted">${roleBadge} ${statusBadge}</small>
										</div>
									</div>
								`;
								
								// Add responder notes if available
								if (assignment.response_notes) {
									responderInfo += `
										<div class="mt-2 pt-2 border-top">
											<small><strong>Notes:</strong></small>
											<div class="text-muted small">${assignment.response_notes.replace(/\n/g, '<br>')}</div>
										</div>
									`;
								}
								
								responderDiv.innerHTML = responderInfo;
								colDiv.appendChild(responderDiv);
								row.appendChild(colDiv);
							}
						});
						
						if (container) {
							$('#modalAllRespondersArea').show();
						}
						
						if (callback) callback(true, primaryResponder);
					} else {
						if (callback) callback(false, null);
					}
				} else {
					if (callback) callback(false, null);
				}
			})
			.catch(error => {
				console.error('Error loading responders:', error);
				if (callback) callback(false, null);
			});
	}

	// Store media data globally for viewer
	let mapModalMedia = [];
	let currentMediaIndex = 0;
	
	// Make functions globally accessible
	window.mapModalMedia = mapModalMedia;
	window.currentMediaIndex = currentMediaIndex;

	// Function to load media for a report
	function loadReportMedia(reportId) {
		const container = document.getElementById('modalMedia');
		const mediaArea = document.getElementById('modalMediaArea');
		
		// Show loader
		if (mediaArea) {
			mediaArea.style.display = 'block';
		}
		if (container) {
			container.innerHTML = `
				<div class="col-12 text-center py-4">
					<div class="spinner-border text-primary" role="status">
						<span class="visually-hidden">Loading...</span>
					</div>
					<p class="text-muted mt-2 mb-0">Loading media...</p>
				</div>
			`;
		}
		
		fetch(`/reports/${reportId}/media`)
			.then(response => response.json())
			.then(data => {
				if (data.status === 'success' && data.media && data.media.length > 0) {
					// Store media globally for viewer
					mapModalMedia = data.media;
					window.mapModalMedia = mapModalMedia;
					
					if (container) {
						container.innerHTML = '';
						
						data.media.forEach((media, index) => {
							const col = document.createElement('div');
							col.className = 'col-md-3 col-6 mb-2';
							
							const mediaUrl = media.url || `/storage/${media.file_path}`;
							const thumbnailUrl = media.thumbnail_url || (media.thumbnail_path ? `/storage/${media.thumbnail_path}` : mediaUrl);
							
							let content = '';
							if (media.file_type === 'image') {
								content = `
									<div class="card border shadow-sm" style="overflow: hidden; cursor: pointer;" onclick="openMapMediaViewer(${index})">
										<div style="width: 100%; height: 100px; overflow: hidden; background: #f8f9fa; display: flex; align-items: center; justify-content: center;">
											<img src="${thumbnailUrl}" 
												 class="card-img-top" 
												 style="width: 100%; height: 100%; object-fit: cover;" 
												 onerror="this.onerror=null; this.src='${mediaUrl}';"
												 loading="lazy"
												 alt="Media image">
										</div>
									</div>
								`;
							} else if (media.file_type === 'video') {
								content = `
									<div class="card border shadow-sm" style="overflow: hidden; position: relative; cursor: pointer;" onclick="openMapMediaViewer(${index})">
										<div style="width: 100%; height: 100px; overflow: hidden; background: #000; position: relative;">
											<video src="${mediaUrl}" 
												   style="width: 100%; height: 100%; object-fit: cover;"
												   muted
												   onmouseover="this.play()"
												   onmouseout="this.pause(); this.currentTime=0;">
											</video>
											<div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); pointer-events: none;">
												<i class="bi bi-play-circle-fill text-white" style="font-size: 2rem; text-shadow: 0 0 10px rgba(0,0,0,0.8);"></i>
											</div>
											<div style="position: absolute; bottom: 5px; right: 5px;">
												<span class="badge bg-dark bg-opacity-75">
													<i class="bi bi-play-circle"></i> Video
												</span>
											</div>
										</div>
									</div>
								`;
							}
							
							col.innerHTML = content;
							container.appendChild(col);
						});
						
						if (mediaArea) {
							mediaArea.style.display = 'block';
						}
					}
				} else {
					mapModalMedia = [];
					window.mapModalMedia = [];
					if (container) {
						container.innerHTML = `
							<div class="col-12 text-center py-3">
								<p class="text-muted mb-0">
									<i class="bi bi-image text-secondary"></i> No media uploaded
								</p>
							</div>
						`;
					}
					if (mediaArea) {
						mediaArea.style.display = 'block';
					}
				}
			})
			.catch(error => {
				console.error('Error loading media:', error);
				mapModalMedia = [];
				window.mapModalMedia = [];
				if (container) {
					container.innerHTML = `
						<div class="col-12 text-center py-3">
							<p class="text-muted mb-0">
								<i class="bi bi-exclamation-circle text-warning"></i> Failed to load media
							</p>
						</div>
					`;
				}
				if (mediaArea) {
					mediaArea.style.display = 'none';
				}
			});
	}

	// Navigation helper functions
	window.showPreviousMedia = function() {
		if (!window.mapModalMedia || window.mapModalMedia.length === 0) return;
		const currentVideo = document.querySelector('#mediaViewerContent video');
		if (currentVideo) {
			currentVideo.pause();
			currentVideo.currentTime = 0;
		}
		window.currentMediaIndex = (window.currentMediaIndex - 1 + window.mapModalMedia.length) % window.mapModalMedia.length;
		window.updateMapMediaViewer();
	};
	
	window.showNextMedia = function() {
		if (!window.mapModalMedia || window.mapModalMedia.length === 0) return;
		const currentVideo = document.querySelector('#mediaViewerContent video');
		if (currentVideo) {
			currentVideo.pause();
			currentVideo.currentTime = 0;
		}
		window.currentMediaIndex = (window.currentMediaIndex + 1) % window.mapModalMedia.length;
		window.updateMapMediaViewer();
	};

	// Function to open media viewer (global scope)
	window.openMapMediaViewer = function(index) {
		if (!window.mapModalMedia || window.mapModalMedia.length === 0) return;
		
		window.currentMediaIndex = index;
		const modalElement = document.getElementById('mediaViewerModal');
		if (!modalElement) return;
		
		const modal = new bootstrap.Modal(modalElement);
		
		// Stop any playing video before opening
		const currentVideo = document.querySelector('#mediaViewerContent video');
		if (currentVideo) {
			currentVideo.pause();
			currentVideo.currentTime = 0;
		}
		
		window.updateMapMediaViewer();
		modal.show();
	}

	// Function to update media viewer content
	window.updateMapMediaViewer = function() {
		if (!window.mapModalMedia || window.mapModalMedia.length === 0) return;
		
		const media = window.mapModalMedia[window.currentMediaIndex];
		const mediaUrl = media.url || `/storage/${media.file_path}`;
		const content = document.getElementById('mediaViewerContent');
		const counter = document.getElementById('mediaViewerCounter');
		const prevBtn = document.getElementById('prevMediaBtn');
		const nextBtn = document.getElementById('nextMediaBtn');
		
		if (!content) return;
		
		// Update counter
		if (counter) {
			counter.textContent = `${window.currentMediaIndex + 1} / ${window.mapModalMedia.length}`;
		}
		
		// Show/hide navigation buttons
		if (prevBtn) {
			prevBtn.style.display = window.mapModalMedia.length > 1 ? 'block' : 'none';
		}
		if (nextBtn) {
			nextBtn.style.display = window.mapModalMedia.length > 1 ? 'block' : 'none';
		}
		
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
				<div class="text-center text-muted">
					<i class="bi bi-file-earmark fs-1"></i>
					<br><p class="mt-2">Unsupported media type</p>
				</div>
			`;
		}
	}

	// Keyboard navigation (set up once, works for all modals)
	document.addEventListener('keydown', function(e) {
		const modalElement = document.getElementById('mediaViewerModal');
		if (!modalElement) return;
		const modal = bootstrap.Modal.getInstance(modalElement);
		if (!modal || !modal._isShown) return;
		
			if (e.key === 'ArrowLeft') {
				e.preventDefault();
				window.showPreviousMedia();
			} else if (e.key === 'ArrowRight') {
				e.preventDefault();
				window.showNextMedia();
			}
	});
	
	function getStatusBadge(status) {
		const statusMap = {
			'assigned': '<span class="badge bg-warning">Assigned</span>',
			'en_route': '<span class="badge bg-info">En Route</span>',
			'on_scene': '<span class="badge bg-primary">On Scene</span>',
			'completed': '<span class="badge bg-success">Completed</span>',
			'cancelled': '<span class="badge bg-danger">Cancelled</span>'
		};
		return statusMap[status] || '<span class="badge bg-secondary">' + status + '</span>';
	}
	
	function checkIfSecondaryResponder(reportId, userId, callback) {
		fetch(`/reports/${reportId}/responders`)
			.then(response => response.json())
			.then(data => {
				if (data.status === 'success' && data.responders) {
					const assignment = data.responders.find(r => r.responder_id === userId);

					if (assignment.status === "completed") {
						callback(true, false, true);
						return;
					}

					if (assignment) {
						callback(true, assignment.role === 'primary');
					} else {
						callback(false, false);
						return;
					}
				} else {
					callback(false, false);
					return;
				}
			})
			.catch(error => {
				console.error('Error checking responder assignment:', error);
				callback(false, false);
				return;
			});
	}
	
	function checkIfHasPrimaryResponder(reportId, callback) {
		fetch(`/reports/${reportId}/responders`)
			.then(response => response.json())
			.then(data => {
				if (data.status === 'success' && data.responders) {
					const hasPrimary = data.responders.some(r => r.role === 'primary');
					callback(hasPrimary);
				} else {
					callback(false);
				}
			})
			.catch(error => {
				console.error('Error checking primary responder:', error);
				callback(false);
			});
	}
	
	function joinAsSecondaryResponder(reportId, userId) {
		// Show confirmation dialog
		Swal.fire({
			title: 'Join as Secondary Responder?',
			text: 'You will join this report as a secondary responder to assist the primary responder.',
			icon: 'question',
			showCancelButton: true,
			confirmButtonText: 'Yes, Join',
			cancelButtonText: 'Cancel',
			buttonsStyling: false,
			customClass: {
				confirmButton: 'btn btn-info',
				cancelButton: 'btn btn-secondary'
			}
		}).then((result) => {
			if (result.isConfirmed) {
				// Send request to join as secondary responder
				fetch(`/responder/respond`, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
					},
					body: JSON.stringify({
						report_id: reportId,
						responder_id: userId,
						type_of_response: 'response',
						response_notes: 'Joined as secondary responder'
					})
				})
				.then(response => response.json())
				.then(data => {
					if (data.status === 'success') {
						// Close the modal first
						const reportModal = bootstrap.Modal.getInstance(document.getElementById('reportModal'));
						if (reportModal) {
							reportModal.hide();
						}
						
						Swal.fire({
							icon: 'success',
							title: 'Success!',
							text: 'You have joined as a secondary responder. Redirecting to response page...',
							confirmButtonText: 'OK',
							buttonsStyling: false,
							customClass: {
								confirmButton: 'btn btn-success'
							},
							timer: 2000,
							timerProgressBar: true,
							allowOutsideClick: false
						}).then(() => {
							// Redirect to response page
							window.location.href = `/response/${reportId}`;
						});
					} else {
						Swal.fire({
							icon: 'error',
							title: 'Error',
							text: data.message || 'Failed to join as secondary responder.',
							confirmButtonText: 'OK',
							buttonsStyling: false,
							customClass: {
								confirmButton: 'btn btn-danger'
							}
						});
					}
				})
				.catch(error => {
					console.error('Error joining as secondary responder:', error);
					Swal.fire({
						icon: 'error',
						title: 'Error',
						text: 'An error occurred while joining as secondary responder.',
						confirmButtonText: 'OK',
						buttonsStyling: false,
						customClass: {
							confirmButton: 'btn btn-danger'
						}
					});
				});
			}
		});
	}

	const modalEl = document.getElementById("reportModal");
	const modalHeader = modalEl.querySelector(".modal-header");

	// Add a class based on report.severity_level if it exists
	if (modalHeader && report.severity_level) {
		// First, remove existing severity classes
		modalHeader.classList.remove("critical", "high", "moderate", "low");
		modalHeader.classList.add(report.severity_level);
	}

	if (!modalInstance) modalInstance = new bootstrap.Modal(modalEl);
	modalInstance.show();

	setTimeout(() => initMiniMap(report), 300);
}

function initMiniMap(report) {
	const localStyleId = localStorage.getItem("selectedMapStyle") || "default_1";
	const localSelectedStyle = mapStyles.find(style => style.id === localStyleId) || mapStyles[0];
	if (!miniMap) {
		miniMap = L.map("miniMap", { zoomControl: false, attributionControl: false })
			.setView([report.latitude, report.longitude], 14);
		L.tileLayer(localSelectedStyle.url).addTo(miniMap);
	} else {
		miniMap.invalidateSize();
		miniMap.setView([report.latitude, report.longitude], 14);
		if (routeControl) routeControl.remove();
		if (miniMarker) miniMap.removeLayer(miniMarker);
	}

	const severity = (report.severity_level || "moderate").toLowerCase();
	const type = (report.type || "others").toLowerCase();

	const routeColor = severityColors[severity] || "#ff5a5f";

	// Add custom marker for report location
	miniMarker = L.marker([report.latitude, report.longitude], {
		icon: createCustomMarker(type, severity),
	}).addTo(miniMap);

	if (navigator.geolocation) {
		return;
		navigator.geolocation.getCurrentPosition((pos) => {
			const userLatLng = L.latLng(pos.coords.latitude, pos.coords.longitude);

			const userIcon = L.divIcon({
				html: `
					<img src="/assets/img/person.png" style="width: 30px" />
				`,
				className: '', // remove default Leaflet styles
				iconSize: [25, 25],
				iconAnchor: [16, 25],
			});
			// Optional: draw user's location as a circle
			L.marker(userLatLng, {icon: userIcon}).addTo(miniMap);

			// 🧭 Routing with severity color + no markers
			routeControl = L.Routing.control({
				waypoints: [userLatLng, L.latLng(report.latitude, report.longitude)],
				draggableWaypoints: false,
				addWaypoints: false,
				routeWhileDragging: false,
				fitSelectedRoutes: true,
				lineOptions: { styles: [{ color: routeColor, weight: 5 }] },
				createMarker: () => null
			}).addTo(miniMap);

			routeControl.on('routesfound', () => {
				const panel = document.querySelector('.leaflet-routing-container');
				if (panel) {
					panel.classList.add('leaflet-routing-container-hide');
				}
			});
		});
	}
}

function displayResponders(data) {
	const { 
		latitude, 
		longitude, 
		message, 
		summary, 
		report_id, 
		responder_name, 
		responder_id,
		responder_role,
		responder_status
	} = data;

	// Allow display even if no location (will use report location as fallback)
	if (!report_id) return;
	
	// Only show responder marker if status is 'en_route' or 'on_scene'
	if (responder_status && !['en_route', 'on_scene'].includes(responder_status)) {
		// Remove marker if it exists and status changed
		const markerKey = `${report_id}-${responder_id}`;
		if (responderMarkers[markerKey]) {
			map.removeLayer(responderMarkers[markerKey]);
			delete responderMarkers[markerKey];
		}
		return;
	}
	
	// If no coordinates provided, skip (shouldn't happen after backend filter, but safety check)
	if (!latitude || !longitude) {
		console.warn('Responder data missing coordinates:', data);
		return;
	}
	const responderLatLng = L.latLng(latitude, longitude);
	const summaryText = summary;
	summaryText
	const userId = typeof currentUserId !== 'undefined' ? currentUserId : (typeof window.currentUserId !== 'undefined' ? window.currentUserId : null);
	const isFromSameUser = responder_id === userId;

	// Determine role label
	const roleLabel = responder_role === 'primary' ? 'Primary' : (responder_role === 'secondary' ? 'Secondary' : 'Responder');
	const roleBadge = `<span class="badge ${responder_role === 'primary' ? 'bg-primary' : 'bg-secondary'}" style="font-size: 0.75em;">${roleLabel}</span>`;
	
	const responderDisplayName = `<strong>Responder:</strong>  ${responder_name} ${isFromSameUser ? '(You)' : ''} ${roleBadge}` || `Responder ${responder_id || ""}`;
	const initailMessage = `${responderDisplayName}<br>` + (message || '');
	const fullMessage = initailMessage + (summary ? `<br><strong>${summary}</strong> ` : '');

	const responderIcon = L.divIcon({
		html: `
        <div class="truck-wrapper">
            <div class="pulse-responder critical"></div>
            <img src="/assets/img/ambulance.png" class="truck-image" />
			${isFromSameUser ? `<div class="responder-label">You</div>`: ''}
        </div>
    `,
		className: '', // remove default Leaflet styles
		iconSize: [20, 30],
		iconAnchor: [15, 25],
	});

	// Use composite key: report_id-responder_id to allow multiple responders per report
	const markerKey = `${report_id}-${responder_id}`;
	
	// 🚑 Create or update responder marker
	if (!responderMarkers[markerKey]) {
		var severity = data.severity_level ? data.severity_level.toLowerCase() : "moderate";
		const routeColor = severityColors[severity] || "#ff5a5f";

		const responderMarker = L.marker(responderLatLng, { icon: responderIcon })
			.addTo(map)
			.bindTooltip(fullMessage || "Responder", {
				permanent: false,
				direction: 'bottom',
				className: 'responder-tooltip'
			});

		responderMarkers[markerKey] = responderMarker;
		// ✅ When ambulance is clicked — draw route (do NOT remove icon)
		responderMarker.on('click', function () {
			$("#map-loader").show();
			if (report_id && reportMarkers[report_id]) {
				drawRouteToReport(report_id, responderLatLng, routeColor);
			} else {
				alert('No matching report found for this responder.');
			}
		});

		map.setView(responderLatLng, 14);
	} else {
		// 🧭 Update position & tooltip of existing responder
		const marker = responderMarkers[markerKey];
		marker.setLatLng(responderLatLng);
		if (marker.getTooltip()) {
			marker.setTooltipContent(fullMessage);
		}
	}

	// 🖱️ Click outside map to remove route (ambulance stays)
	map.on('click', function () {
		removeAllRoutes();
	});
}

// 🧭 Draw route from ambulance to report
function drawRouteToReport(reportId, responderLatLng, routeColor = '#007bff') {
	const report = reports.find(r => r.id === reportId);
	if (!report) return;

	const reportLatLng = L.latLng(report.latitude, report.longitude);

	// Remove previous route if exists
	if (routeControl) {
		map.removeControl(routeControl);
		routeControl = null;
	}

	// ❌ Remove blue pin for that report
	if (reportMarkers[reportId]) {
		const reportMarker = reportMarkers[reportId];
		if (map.hasLayer(reportMarker)) map.removeLayer(reportMarker);
	}

	// 🛣️ Draw new route (no default blue start/end icons)
	routeControl = L.Routing.control({
		waypoints: [responderLatLng, reportLatLng],
		addWaypoints: false,
		draggableWaypoints: false,
		routeWhileDragging: false,
		fitSelectedRoutes: false,
		lineOptions: { styles: [{ color: routeColor, weight: 5 }] }, // <-- use routeColor
		createMarker: () => null
	}).addTo(map);

	routeControl.on('routesfound', function(e) {
		$("#map-loader").fadeOut(100);
	});
	
	// // Optionally, handle errors
	// routeControl.on('routingerror', function(err) {
	// 	console.error("Routing error:", err);
	// 	$("#map-loader").fadeOut(100);
	// });

	const panel = document.querySelector('.leaflet-routing-container');
	if (panel) panel.classList.add('hide');
}

function displayUserLocation() {
	if (navigator.geolocation) {
		navigator.geolocation.getCurrentPosition((position) => {
			const lat = position.coords.latitude;
			const lng = position.coords.longitude;

			const userIcon = L.divIcon({
				html: `
					<img src="/assets/img/person.png" style="width: 30px" />
				`,
				className: '', // remove default Leaflet styles
				iconSize: [25, 25],
				iconAnchor: [16, 25],
			});

			L.marker([lat, lng], { icon: userIcon })
				.addTo(map)
				.bindPopup("<b>You are here</b>", {
					offset: L.point(0, -15)
				})
				.openPopup();
		});
	}
}

function hideUserLocation() {
	navigator.geolocation.getCurrentPosition((position) => {
		const lat = position.coords.latitude;
		const lng = position.coords.longitude;

		map.eachLayer((layer) => {
			if (layer instanceof L.Marker) {
				const markerLatLng = layer.getLatLng();
				if (markerLatLng.lat === lat && markerLatLng.lng === lng) {
					map.removeLayer(layer);
				}
			}
		});
	});
}

// create form
// Initialize map create
function initMap(lat, lng) {
	// If map already exists, just update it
	if (mapCreate) {
		mapCreate.setView([lat, lng], 15);
		marker.setLatLng([lat, lng]);
		mapCreate.invalidateSize();
		setCoordinates(lat, lng);
		getAddress(lat, lng);
		return;
	}

	// Create the map only once
	mapCreate = L.map('map_create', {
		attributionControl: false,
		zoomControl: false
	}).setView([lat, lng], 15);

	L.tileLayer(selectedStyle.url, {
		attribution: selectedStyle.attribution || ''
	}).addTo(mapCreate);

	// Add draggable marker
	marker = L.marker([lat, lng], { draggable: true }).addTo(mapCreate);

	mapCreate.invalidateSize(); // ensure proper layout

	// Set default coords
	setCoordinates(lat, lng);
	getAddress(lat, lng);

	// Marker drag handler
	marker.on('dragend', function () {
		const pos = marker.getLatLng();
		setCoordinates(pos.lat, pos.lng);
		getAddress(pos.lat, pos.lng);
	});

	// Map click handler
	mapCreate.on('click', function (e) {
		const { lat, lng } = e.latlng;
		marker.setLatLng([lat, lng]);
		setCoordinates(lat, lng);
		getAddress(lat, lng);
	});
}

// Save coordinates to hidden inputs
function setCoordinates(lat, lng) {
	document.getElementById('latitude').value = lat;
	document.getElementById('longitude').value = lng;
}

// Reverse geocode to get address
function getAddress(lat, lng) {
	fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
		.then(res => res.json())
	.then(data => {
		const address = data.display_name || 'Unknown location';
		document.getElementById('address').value = address;
	})
	.catch(() => {
		document.getElementById('address').value = 'Unable to fetch address';
	});
}

function showReportsByUser(userId) {
	removeAllRoutes();
	for (const reportId in reportMarkers) {
		const reportData = reportMarkers[reportId];
		if (reportData.report.user_id === userId) {
			if (!map.hasLayer(reportData.marker)) {
				reportData.marker.addTo(map);
				reportData.pulse.addTo(map);
			}
		} else {
			if (map.hasLayer(reportData.marker)) {
				map.removeLayer(reportData.marker);
				map.removeLayer(reportData.pulse);
			}
		}
	}

	//responderMarkers - handle composite keys (report_id-responder_id)
	for (const markerKey in responderMarkers) {
		const responderMarker = responderMarkers[markerKey];
		const [reportId] = markerKey.split('-');
		const associatedReport = reportMarkers[reportId];
		if (associatedReport && associatedReport.report.user_id === userId) {
			if (!map.hasLayer(responderMarker)) {
				responderMarker.addTo(map);
			}
		} else {
			if (map.hasLayer(responderMarker)) {
				map.removeLayer(responderMarker);
			}
		}
	}
}

function showReportsByResponder(responderId) {
	removeAllRoutes();
	for (const reportId in reportMarkers) {
		const reportData = reportMarkers[reportId];
		
		// Check if responder is assigned to this report
		// Handle both array format and object format
		let isAssigned = false;
		if (reportData.report.responders) {
			if (Array.isArray(reportData.report.responders)) {
				isAssigned = reportData.report.responders.some(r => r.responder_id == responderId || r.id == responderId);
			} else if (typeof reportData.report.responders === 'object') {
				// Handle object format (if responders is an object with keys)
				isAssigned = Object.values(reportData.report.responders).some(r => r.responder_id == responderId || r.id == responderId);
			}
		}
		
		if (isAssigned) {
			if (!map.hasLayer(reportData.marker)) {
				reportData.marker.addTo(map);
				reportData.pulse.addTo(map);
			}
		} else {
			if (map.hasLayer(reportData.marker)) {
				map.removeLayer(reportData.marker);
				map.removeLayer(reportData.pulse);
			}
		}
	}

	// Update to handle composite keys (report_id-responder_id)
	for (const markerKey in responderMarkers) {
		const responderMarker = responderMarkers[markerKey];
		const [reportId, resId] = markerKey.split('-');
		const associatedReport = reportMarkers[reportId];
		
		// Check if this responder is assigned to any report the user is responding to
		let isAssignedToReport = false;
		if (associatedReport) {
			if (resId == responderId) {
				isAssignedToReport = true;
			} else if (associatedReport.report.responders) {
				if (Array.isArray(associatedReport.report.responders)) {
					isAssignedToReport = associatedReport.report.responders.some(r => r.responder_id == responderId || r.id == responderId);
				} else if (typeof associatedReport.report.responders === 'object') {
					isAssignedToReport = Object.values(associatedReport.report.responders).some(r => r.responder_id == responderId || r.id == responderId);
				}
			}
		}
		
		if (isAssignedToReport) {
			if (!map.hasLayer(responderMarker)) {
				responderMarker.addTo(map);
			}
		} else {
			if (map.hasLayer(responderMarker)) {
				map.removeLayer(responderMarker);
			}
		}
	}
}

function resetReportMarkers() {
	removeAllRoutes();
	for (const reportId in reportMarkers) {
		const reportData = reportMarkers[reportId];
		if (!map.hasLayer(reportData.marker)) {
			reportData.marker.addTo(map);
			reportData.pulse.addTo(map);
		}
	}

	// Display all responder markers (using composite keys)
	for (const markerKey in responderMarkers) {
		const responderMarker = responderMarkers[markerKey];
		if (!map.hasLayer(responderMarker)) {
			responderMarker.addTo(map);
		}
	}
}

function removeAllRoutes() {
	if (routeControl) {
		const panel = document.querySelector('.leaflet-routing-container');
		if (panel) panel.classList.remove('hide');
		map.removeControl(routeControl);
		routeControl = null;
	}
}

function removeReportMarker(markerId) {
	if (reportMarkers[markerId]) {
		const reportMarker = reportMarkers[markerId];
		if (map.hasLayer(reportMarker.marker)) map.removeLayer(reportMarker.marker);
		if (map.hasLayer(reportMarker.pulse)) map.removeLayer(reportMarker.pulse);
		delete reportMarkers[markerId];
	}

	// Remove responder markers for this report - handle composite keys (report_id-responder_id)
	for (const markerKey in responderMarkers) {
		if (markerKey.startsWith(markerId + '-')) {
			const responderMarker = responderMarkers[markerKey];
			if (map.hasLayer(responderMarker)) map.removeLayer(responderMarker);
			delete responderMarkers[markerKey];
		}
	}
}

// Check if report has responders and update delete button in map modal
async function checkRespondersForDeleteButton(reportId, deleteBtn) {
	if (!deleteBtn || !reportId) return;
	
	try {
		const response = await fetch(`/reports/${reportId}/responders`);
		const data = await response.json();
		
		if (data.status === 'success' && data.responders && data.responders.length > 0) {
			// Has responders - disable delete button
			deleteBtn.disabled = true;
			deleteBtn.classList.add('disabled');
			deleteBtn.setAttribute('title', 'Cannot delete report. There are responders assigned to this report.');
		} else {
			// No responders - enable delete button
			deleteBtn.disabled = false;
			deleteBtn.classList.remove('disabled');
			deleteBtn.removeAttribute('title');
		}
	} catch (error) {
		console.error('Error checking responders:', error);
	}
}

// Delete report from map modal
async function deleteReportFromMap() {
	const deleteBtn = document.getElementById('deleteReportBtn');
	if (!deleteBtn) return;
	
	const reportId = deleteBtn.getAttribute('data-report-id');
	if (!reportId) return;
	
	// Check if button is disabled (responders assigned)
	if (deleteBtn.disabled) {
		Swal.fire({
			icon: 'warning',
			title: 'Cannot Delete',
			text: 'Cannot delete report. There are responders assigned to this report. Please cancel or complete the response first.',
			confirmButtonText: 'OK'
		});
		return;
	}
	
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
			// Close the modal
			const modal = bootstrap.Modal.getInstance(document.getElementById('reportModal'));
			if (modal) modal.hide();
			
			// Remove marker from map (will also be removed via real-time update)
			removeReportMarker(reportId);
			
			Swal.fire({
				icon: 'success',
				title: 'Deleted!',
				text: data.message || 'Report has been deleted.',
				timer: 2000,
				showConfirmButton: false
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

// Cancel report from map modal
async function cancelReportFromMap() {
	const cancelBtn = document.getElementById('cancelReportBtn');
	if (!cancelBtn) return;
	
	const reportId = cancelBtn.getAttribute('data-report-id');
	if (!reportId) return;
	
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
			// Close the modal
			const modal = bootstrap.Modal.getInstance(document.getElementById('reportModal'));
			if (modal) modal.hide();
			
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
