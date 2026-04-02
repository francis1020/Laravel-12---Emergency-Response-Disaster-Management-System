<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta http-equiv="Permissions-Policy" content="unload=()">
    <meta http-equiv="Permissions-Policy" content="*">
    <meta charset="utf-8">
    <title>@yield('title', config('app.name', 'Laravel')) | {{ config('app.name', 'Laravel') }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- ** Mobile Specific Metas ** -->
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="Vex HTML Template">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">

    <!-- theme meta -->
    <link rel="icon" type="image/png" href="{{ asset('assets/img/logo2.png') }}" sizes="32x32">

    <!-- ** Plugins Needed for the Project ** -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Droid&#43;Serif:400%7cJosefin&#43;Sans:300,400,600,700 ">
    <link rel="stylesheet" href="{{ asset('assets/plugins/bootstrap/bootstrap.min.css') }}">
    <!-- <link rel="stylesheet" href="{{ asset('assets/plugins/themefisher-font/themefisher-font.min.css ') }}"> -->
    <!-- <link rel="stylesheet" href="{{ asset('assets/plugins/slick/slick.min.css') }}"> -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />

    <!-- Stylesheets -->
    <link href="{{ asset('assets/css/style.css') }}" rel="stylesheet">

    <!--Favicon-->
    <!-- <link rel="icon" href="images/favicon.png" type="image/x-icon"> -->

    <!-- Pusher-->
    <script src="https://js.pusher.com/8.2/pusher.min.js"></script>

    <!-- Leaflet -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />

    <!-- Leaflet Routing Machine -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.css" />

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet" />
</head>

<body id="body" style="overflow-x: hidden;">

    <script>
        const pusher = new Pusher("9e5652fdc4ba95431707", {
            cluster: "ap1",
            forceTLS: true
        });
    </script>

    <!-- preloader start -->
    <div class="preloader">
        <div class="ambulance-container">
            <img src="{{ asset('assets/img/ambulance.png') }}" alt="ambulance" class="ambulance">
            <div class="loading-text">
                <span>L</span><span>o</span><span>a</span><span>d</span><span>i</span><span>n</span><span>g</span><span>...</span>
            </div>
        </div>
    </div>

    <!-- preloader end -->

    <!-- navigation start -->
    @include('layouts.navigation')
    <!-- navigation end -->

    <!-- script start -->
    <script>
        const typeIcons = {
            @foreach ($emergencyTypes as $type)
                "{{ $type->code }}": "{{ $type->icon }}",
            @endforeach
        };

        const severityColors = {
            critical: "#dc3545",   // Red
            high: "#fd7e14",       // Orange
            moderate: "#ffc107",   // Yellow
            low: "#28a745",        // Green
        };

        const currentUserId = {{ Auth::check() ? Auth::id() : 'null' }};
    </script>

    <script src="{{ asset('assets/plugins/jquery/jquery.js') }}"></script>
    <script src="{{ asset('assets/plugins/bootstrap/bootstrap.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/slick/slick.min.js') }}"></script>
    <script src="{{ asset('assets/js/script.js') }}"></script>

    <!-- Leaflet -->
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

    <!-- Leaflet Routing Machine -->
    <script src="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.min.js"></script>

    <!-- Leaflet-Rotate plugin -->
    <script src="https://cdn.jsdelivr.net/gh/Raruto/leaflet-rotate@main/dist/leaflet-rotate.min.js"></script>

    <!-- Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    <!-- script end -->
    <main>
        <div class="no-gutters">
            @if(Auth::check() && (request()->routeIs('admin.*') || request()->routeIs('dashboard') || request()->routeIs('reports.history') || request()->routeIs('resources.*') || request()->routeIs('analytics.*') || request()->routeIs('reports.show') || request()->routeIs('messages.*')))
                @include('layouts.admin-sidebar')
                <div class="admin-content-wrapper">
                    <div class="admin-content">
                        @yield('content')
                    </div>
                </div>
            @else
                <div class="col-md-12">
                    @yield('content')
                </div>
            @endif
        </div>
    </main>

    <style>
    body {
        overflow-x: hidden;
    }
    
    /* SweetAlert2 Button Spacing */
    .swal2-actions {
        gap: 0.75rem !important;
        margin-top: 1rem !important;
    }
    
    .swal2-actions button {
        margin: 0 !important;
        padding: 0.5rem 1.5rem !important;
    }
    
    .swal2-actions button:not(:last-child) {
        margin-right: 0.5rem !important;
    }

    main {
        overflow-x: hidden;
    }

    .admin-content-wrapper {
        margin-left: 250px;
        width: calc(100% - 250px);
        transition: margin-left 0.3s ease, width 0.3s ease;
        box-sizing: border-box;
        overflow-x: hidden;
    }

    .admin-content {
        padding: 0 0;
        min-height: calc(100vh - 70px);
        width: 100%;
        max-width: 100%;
        box-sizing: border-box;
        overflow-x: hidden;
    }

    .admin-content .container-fluid {
        max-width: 100%;
        padding-left: 1rem;
        padding-right: 1rem;
        margin-left: 0;
        margin-right: 0;
    }

    @media (max-width: 768px) {
        .admin-content-wrapper {
            margin-left: 0 !important;
            width: 100% !important;
        }
        
        .admin-sidebar {
            position: fixed;
            top: 70px;
            left: 0;
            z-index: 1050;
        }
        
        .sidebar-toggle-btn {
            display: block !important;
        }
    }
    
    @media (min-width: 769px) {
        .sidebar-toggle-btn {
            display: none !important;
        }
    }
    </style>

    <script>
        // Define functions globally before DOMContentLoaded to ensure they're available
        @if(Auth::check())
        // Function to update message badge (make it globally accessible)
        function updateMessageBadge(count) {
            const badge = document.getElementById('messageBadge');
            if (badge) {
                if (count > 0) {
                    badge.textContent = count > 99 ? '99+' : count;
                    badge.style.display = 'inline-block';
                } else {
                    badge.style.display = 'none';
                }
            }
        }
        
        // Function to load unread message count (make it globally accessible immediately)
        window.loadUnreadMessageCount = async function loadUnreadMessageCount() {
            try {
                const response = await fetch('{{ route("messages.unread-count") }}');
                const data = await response.json();
                if (data.status === 'success') {
                    updateMessageBadge(data.unread_count);
                }
            } catch (error) {
                console.error('Error loading unread count:', error);
            }
        };
        
        document.addEventListener('DOMContentLoaded', function () {
            // Load unread message count
            loadUnreadMessageCount();
            
            // Load unread notifications count
            if (typeof loadUnreadNotificationCount === 'function') {
                loadUnreadNotificationCount();
            }
            
            // Store current messages for comparison
            let currentMessages = [];
            
            // Function to load recent messages for dropdown (make it globally accessible)
            window.loadRecentMessages = async function loadRecentMessages() {
                try {
                    const response = await fetch('{{ route("messages.recent") }}');
                    const data = await response.json();
                    if (data.status === 'success') {
                        // Check if there are new reports (compare report IDs)
                        const newReportIds = (data.reports || []).map(r => r.report_id);
                        const currentReportIds = (currentMessages || []).map(r => r.report_id);
                        const hasNewMessages = newReportIds.some(id => !currentReportIds.includes(id));
                        
                        // Update current messages (reports)
                        currentMessages = data.reports || [];
                        
                        // Display reports (grouped by report)
                        displayRecentMessages(data.reports || []);
                        updateMessageBadge(data.unread_count);
                        
                        // Show visual indicator if new messages arrived
                        if (hasNewMessages && (data.reports || []).length > 0) {
                            // Highlight the dropdown icon briefly
                            const messageIcon = document.querySelector('[data-bs-toggle="dropdown"] i.bi-chat-dots');
                            if (messageIcon) {
                                messageIcon.classList.add('text-primary');
                                messageIcon.style.animation = 'pulse 0.5s ease-in-out';
                                setTimeout(() => {
                                    messageIcon.style.animation = '';
                                }, 500);
                            }
                        }
                    }
                } catch (error) {
                    console.error('Error loading recent messages:', error);
                    const list = document.getElementById('messagesList');
                    if (list) {
                        list.innerHTML = '<small class="text-muted text-center">Error loading messages</small>';
                    }
                }
            }
            
            // Function to format time ago
            function getTimeAgo(timestamp) {
                const now = Math.floor(Date.now() / 1000);
                const diff = now - timestamp;
                
                // Less than 1 minute: show "now"
                if (diff < 60) {
                    return 'now';
                } 
                // 1 minute to 4 hours: show minutes/hours
                else if (diff < 14400) { // 4 hours = 14400 seconds
                    const minutes = Math.floor(diff / 60);
                    if (minutes < 60) {
                        return `${minutes} min`;
                    } else {
                        const hours = Math.floor(minutes / 60);
                        return `${hours} hr`;
                    }
                } 
                // 4 hours or more: show date
                else {
                    const date = new Date(timestamp * 1000);
                    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                }
            }
            
            // Function to update time displays in message dropdown
            function updateMessageTimes() {
                document.querySelectorAll('.message-item-notification[data-timestamp]').forEach(item => {
                    const timestamp = parseInt(item.getAttribute('data-timestamp'));
                    const timeElement = item.querySelector('.message-time');
                    if (timeElement && timestamp) {
                        timeElement.textContent = getTimeAgo(timestamp);
                    }
                });
            }
            
            // Function to display recent messages in dropdown (grouped by report)
            function displayRecentMessages(reports) {
                const list = document.getElementById('messagesList');
                if (!list) return;
                
                if (!reports || reports.length === 0) {
                    list.innerHTML = '<small class="text-muted text-center d-block py-3">No new messages</small>';
                    return;
                }
                
                let html = '';
                reports.forEach(report => {
                    const latestMsg = report.latest_message;
                    const timestamp = latestMsg.created_at_timestamp || Math.floor(new Date(latestMsg.created_at_full).getTime() / 1000);
                    html += `
                        <div class="px-3 py-2 border-bottom message-item-notification bg-light" style="cursor: pointer;" data-report-id="${report.report_id}" data-timestamp="${timestamp}">
                            <div class="d-flex align-items-start">
                                <i class="bi bi-chat-dots-fill me-2 mt-1 text-primary"></i>
                                <div class="flex-grow-1" style="min-width: 0;">
                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                        <div class="fw-semibold text-primary" style="font-size: 0.85rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                            Report #${report.report_id} - ${escapeHtml(report.report_type)}
                                        </div>
                                        <span class="badge bg-danger rounded-pill ms-2" style="font-size: 0.65rem; min-width: 20px;">
                                            ${report.unread_count}
                                        </span>
                                    </div>
                                    <div class="text-dark mb-1" style="font-size: 0.8rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                        <strong>${escapeHtml(latestMsg.sender)}:</strong> ${escapeHtml(latestMsg.message)}
                                    </div>
                                    <small class="text-muted message-time">${getTimeAgo(timestamp)}</small>
                                </div>
                            </div>
                        </div>
                    `;
                });
                
                list.innerHTML = html;
                
                // Add click handlers
                document.querySelectorAll('.message-item-notification').forEach(item => {
                    item.addEventListener('click', function() {
                        const reportId = this.getAttribute('data-report-id');
                        if (reportId) {
                            // Store report_id in sessionStorage instead of URL
                            sessionStorage.setItem('openReportId', reportId);
                            // Navigate to messages page without report_id parameter (plain URL)
                            window.location.href = '{{ route("messages.index") }}';
                        } else {
                            // No report_id, just navigate to messages page
                            window.location.href = '{{ route("messages.index") }}';
                        }
                    });
                });
                
                // Update times immediately
                updateMessageTimes();
            }
            
            // Update message times every second for real-time updates
            setInterval(updateMessageTimes, 1000);
            
            // Load recent messages on page load
            loadRecentMessages();
            
            // Subscribe to global message channels for real-time updates
            if (typeof pusher !== 'undefined') {
                const currentUserId = {{ auth()->id() }};
                
                // Subscribe to message.sent channel
                try {
                    const messageSentChannel = pusher.subscribe('message.sent');
                    
                    messageSentChannel.bind('message.sent', function(eventData) {
                        if (eventData.message) {
                            const message = eventData.message;
                            const reportId = message.emergency_report_id;
                            
                            if (!reportId) {
                                console.error('Message missing emergency_report_id', message);
                                return;
                            }
                            
                            // Only reload if message is not from current user (unread message)
                            if (message.user_id !== currentUserId) {
                                loadUnreadMessageCount(); // Reload count
                                loadRecentMessages(); // Reload recent messages dropdown (grouped by report)
                            } else {
                                // Message from current user - still update recent messages to show latest
                                loadRecentMessages();
                            }
                        }
                    });
                } catch (error) {
                    console.error('Error subscribing to message.sent channel:', error);
                }
                
                // Subscribe to message.read channel
                try {
                    const messageReadChannel = pusher.subscribe('message.read');
                    
                    messageReadChannel.bind('message.read', function(eventData) {
                        if (eventData.messageIds && Array.isArray(eventData.messageIds) && eventData.reportId) {
                            // Update when messages are marked as read
                            loadUnreadMessageCount(); // Reload count
                            loadRecentMessages(); // Reload recent messages dropdown
                        }
                    });
                } catch (error) {
                    console.error('Error subscribing to message.read channel:', error);
                }
                
                // Subscribe to user-specific responder notification channel for real-time updates
                try {
                    const userChannel = pusher.subscribe('user.' + currentUserId);
                    
                    userChannel.bind('responder.notification', function(eventData) {
                        // Laravel broadcasts data in eventData.data
                        const notification = eventData.data || eventData;
                        
                        if (notification) {
                            // Check if this is an auto-assignment
                            if (notification.action === 'auto_assigned' && notification.report_id) {
                                // For auto-assign: Wait for any existing toasts to finish, then show modal
                                // Don't add to notification list
                                if (typeof Swal !== 'undefined') {
                                    // Wait a bit to ensure "new report" notification toast is shown first
                                    setTimeout(() => {
                                        // Close any existing toasts before showing modal
                                        Swal.close();
                                        
                                        // Small delay to ensure toast is closed
                                        setTimeout(() => {
                                            Swal.fire({
                                                icon: 'info',
                                                title: 'You Have Been Assigned!',
                                                html: `
                                                    <p>You have been automatically assigned as Primary Responder to:</p>
                                                    <p><strong>Report #${notification.report_id}</strong></p>
                                                    <p><strong>Type:</strong> ${notification.report_type || 'Emergency'}</p>
                                                    <p><strong>Address:</strong> ${notification.report_address || 'N/A'}</p>
                                                    <p><strong>Severity:</strong> ${notification.report_severity || 'N/A'}</p>
                                                `,
                                                showCancelButton: false,
                                                confirmButtonText: 'Continue',
                                                confirmButtonColor: '#3085d6',
                                                allowOutsideClick: false,
                                                allowEscapeKey: false,
                                                timer: false, // Don't auto-close
                                                showCloseButton: false // Don't show close button
                                            }).then((result) => {
                                                if (result.isConfirmed) {
                                                    // Redirect to response page
                                                    window.location.href = `/response/${notification.report_id}`;
                                                }
                                            });
                                        }, 500);
                                    }, 2500); // Wait 2.5 seconds after notification to show modal
                                }
                            } else {
                                // For other notifications: Add to notification list and show toast
                                // Refresh notifications from database to get the latest
                                loadUnreadNotifications();
                                
                                // Also refresh notification count
                                if (typeof loadUnreadNotificationCount === 'function') {
                                    loadUnreadNotificationCount();
                                }
                                
                                // Show toast notification for other notifications
                                if (typeof Swal !== 'undefined') {
                                    // Show toast notification for other notifications
                                    const iconMap = {
                                        'info': 'info',
                                        'success': 'success',
                                        'warning': 'warning',
                                        'error': 'error'
                                    };
                                    
                                    // Determine icon based on action if type is not set
                                    let icon = iconMap[notification.type] || 'info';
                                    if (notification.action === 'cancelled') {
                                        icon = 'warning';
                                    } else if (notification.action === 'resolved') {
                                        icon = 'success';
                                    }
                                    
                                    const title = notification.responder_name ? 
                                        `${notification.responder_name} (${notification.role === 'primary' ? 'Primary' : 'Secondary'})` : 
                                        'Responder Update';
                                    
                                    const message = notification.message || 
                                        (notification.action === 'cancelled' ? 'has cancelled their response' : 'Responder status updated');
                                    
                                    Swal.fire({
                                        toast: true,
                                        position: 'top-end',
                                        icon: icon,
                                        title: title,
                                        text: message,
                                        showConfirmButton: false,
                                        showCloseButton: true,
                                        timer: 5000,
                                        timerProgressBar: true
                                    });
                                }
                            }
                        }
                    });
                } catch (error) {
                    console.error('Error subscribing to responder notification channel:', error);
                }
            }
            
            // Function to load unread notifications from database
            window.loadUnreadNotifications = async function loadUnreadNotifications() {
                try {
                    const response = await fetch('{{ route("notifications.unread") }}');
                    const data = await response.json();
                    
                    if (data.status === 'success') {
                        updateNotificationBadge(data.count || 0);
                        updateNotificationList(data.notifications || []);
                    }
                } catch (error) {
                    console.error('Error loading notifications:', error);
                }
            };
            
            // Function to load unread notification count
            window.loadUnreadNotificationCount = async function loadUnreadNotificationCount() {
                try {
                    const response = await fetch('{{ route("notifications.unread-count") }}');
                    const data = await response.json();
                    
                    if (data.status === 'success') {
                        updateNotificationBadge(data.unread_count || 0);
                    }
                } catch (error) {
                    console.error('Error loading notification count:', error);
                }
            };
            
            // Function to mark all notifications as read
            window.markAllNotificationsAsRead = async function markAllNotificationsAsRead() {
                try {
                    const response = await fetch('{{ route("notifications.mark-all-read") }}', {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    });
                    
                    const data = await response.json();
                    if (data.status === 'success') {
                        // Reload notifications to update the list
                        loadUnreadNotifications();
                        // Update badge count
                        if (typeof loadUnreadNotificationCount === 'function') {
                            loadUnreadNotificationCount();
                        }
                        
                        // Show success message
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                toast: true,
                                position: 'top-end',
                                icon: 'success',
                                title: 'All notifications marked as read',
                                showConfirmButton: false,
                                timer: 2000
                            });
                        }
                    }
                } catch (error) {
                    console.error('Error marking all notifications as read:', error);
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'error',
                            title: 'Failed to mark all as read',
                            showConfirmButton: false,
                            timer: 2000
                        });
                    }
                }
            };
            
            // Function to add notification (now just refreshes from database)
            function addNotification(title, message, type = 'info', reportId = null) {
                // Refresh notifications from database instead of adding to array
                // Add a small delay to ensure notification is saved to database first
                setTimeout(() => {
                    loadUnreadNotifications();
                }, 500);
            }
            
            // Function to update notification badge
            function updateNotificationBadge(count) {
                const badge = document.getElementById('notificationBadge');
                const markAllBtn = document.getElementById('markAllNotificationsRead');
                
                if (badge) {
                    if (count > 0) {
                        badge.textContent = count > 99 ? '99+' : count;
                        badge.style.display = 'inline-block';
                    } else {
                        badge.style.display = 'none';
                    }
                }
                
                // Enable/disable "Read All" button based on notification count
                if (markAllBtn) {
                    if (count > 0) {
                        markAllBtn.style.display = 'inline-block';
                        markAllBtn.disabled = false;
                        markAllBtn.style.opacity = '1';
                        markAllBtn.style.cursor = 'pointer';
                    } else {
                        markAllBtn.style.display = 'none';
                        markAllBtn.disabled = true;
                        markAllBtn.style.opacity = '0.5';
                        markAllBtn.style.cursor = 'not-allowed';
                    }
                }
            }
            
            // Function to update notification times in real-time
            function updateNotificationTimes() {
                document.querySelectorAll('.notification-item[data-timestamp]').forEach(item => {
                    const timestamp = parseInt(item.getAttribute('data-timestamp'));
                    const timeElement = item.querySelector('.notification-time');
                    if (timeElement && timestamp) {
                        timeElement.textContent = getTimeAgo(timestamp);
                    }
                });
            }
            
            // Function to update notification list
            function updateNotificationList(notifications) {
                const list = document.getElementById('notificationsList');
                if (!list) return;
                
                if (!notifications || notifications.length === 0) {
                    list.innerHTML = '<small class="text-muted text-center d-block py-3">No notifications</small>';
                    return;
                }
                
                let html = '';
                notifications.slice(0, 10).forEach(notif => {
                    const iconClass = {
                        'info': 'bi-info-circle text-info',
                        'success': 'bi-check-circle text-success',
                        'warning': 'bi-exclamation-triangle text-warning',
                        'error': 'bi-x-circle text-danger'
                    }[notif.type] || 'bi-info-circle text-info';
                    
                    const timestamp = notif.timestamp || Math.floor(new Date(notif.created_at).getTime() / 1000);
                    
                    html += `
                        <div class="px-3 py-2 border-bottom notification-item" style="cursor: pointer;" data-notification-id="${notif.id}" data-report-id="${notif.reportId || ''}" data-timestamp="${timestamp}">
                            <div class="d-flex align-items-start">
                                <i class="bi ${iconClass} me-2 mt-1"></i>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold" style="font-size: 0.9rem;">${escapeHtml(notif.title)}</div>
                                    <div class="text-muted" style="font-size: 0.8rem;">${escapeHtml(notif.message)}</div>
                                    <small class="text-muted notification-time">${getTimeAgo(timestamp)}</small>
                                </div>
                            </div>
                        </div>
                    `;
                });
                
                list.innerHTML = html;
                
                // Add click handlers
                document.querySelectorAll('.notification-item').forEach(item => {
                    item.addEventListener('click', async function() {
                        const notificationId = this.getAttribute('data-notification-id');
                        const reportId = this.getAttribute('data-report-id');
                        
                        // Mark notification as read
                        if (notificationId) {
                            try {
                                await fetch(`/notifications/${notificationId}/read`, {
                                    method: 'PATCH',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                                    }
                                });
                            } catch (error) {
                                console.error('Error marking notification as read:', error);
                            }
                        }
                        
                        // Navigate to report if reportId exists
                        if (reportId) {
                            window.location.href = '{{ route("reports.map") }}?report_id=' + reportId;
                        } else {
                            // Reload notifications after marking as read
                            loadUnreadNotifications();
                        }
                    });
                });
                
                // Update times immediately
                updateNotificationTimes();
            }
            
            // Load notifications on page load
            loadUnreadNotifications();
            
            // Add click handler for "Read All" button
            const markAllReadBtn = document.getElementById('markAllNotificationsRead');
            if (markAllReadBtn) {
                markAllReadBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    if (typeof markAllNotificationsAsRead === 'function') {
                        markAllNotificationsAsRead();
                    }
                });
            }
            
            // Update notification times every second for real-time updates
            setInterval(() => {
                updateNotificationTimes();
            }, 1000);
            
            // Refresh notifications every 30 seconds
            setInterval(() => {
                loadUnreadNotifications();
            }, 30000);
            
            function escapeHtml(text) {
                if (!text) return '';
                const map = {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;'
                };
                return text.toString().replace(/[&<>"']/g, m => map[m]);
            }
            
            // Listen for emergency location updates (notifications)
            const emergencyLocation = pusher.subscribe('emergency_location');
            emergencyLocation.bind('message.emergency_location', function (res) {
				const data = res.data;

				var userId = {{ Auth::id() ?? 'null' }};

				@if(isset($isValidatedUser) && $isValidatedUser)
					let icon = 'info';
					let title = 'Report location updated';
					let text = '';
					
					// Extract responder information
					let responderName = '';
					let responderRole = '';
					let responderLabel = '';
					
					// Check if responder info is directly in data
					if (data.responder_name && data.responder_role) {
						responderName = data.responder_name;
						responderRole = data.responder_role;
					} else if (data.responders && Array.isArray(data.responders) && data.responders.length > 0) {
						// Find the responder based on status or get the primary responder
						let activeResponder = data.responders.find(r => 
							(r.status === 'assigned' && data.status === 'acknowledged') ||
							(r.status === 'en_route' && data.status === 'dispatched') ||
							(r.status === 'on_scene' && data.status === 'in_progress') ||
							(r.status === 'completed' && data.status === 'resolved')
						);
						
						// If no match found, try to get primary responder, or first responder
						if (!activeResponder) {
							activeResponder = data.responders.find(r => r.role === 'primary') || data.responders[0];
						}
						
						if (activeResponder) {
							responderName = activeResponder.responder?.name || activeResponder.responder_name || 'Responder';
							responderRole = activeResponder.role || 'secondary';
						}
					}
					
					// Format responder label
					if (responderName) {
						const roleText = responderRole === 'primary' ? 'Primary' : 'Secondary';
						responderLabel = `${responderName} (${roleText})`;
					}
					
					// Check if a secondary responder cancelled (has cancelled_responder_id)
					// This takes priority over status checks since status reflects primary responder
					if (data.cancelled_responder_id) {
						icon = 'warning';
						title = `Secondary Responder Cancelled | Report #${data.id}`;
						if (responderLabel && responderRole === 'secondary') {
							text = `${responderLabel} has cancelled their response. The primary responder continues.`;
						} else if (responderName) {
							text = `${responderName} (Secondary) has cancelled their response. The primary responder continues.`;
						} else {
							text = 'A secondary responder has cancelled their response.';
						}
					} else if (data.status == "acknowledged") {
						icon = 'warning';
						title = `Report #${data.id} acknowledged`;
						if (responderLabel) {
							text = `${responderLabel} has been assigned.`;
						} else {
							text = 'Responder has been assigned.';
						}
						if (data.response_notes) {
							text += ` Message: ${data.response_notes}`;
						}
					} else if (data.status == "dispatched") {
						icon = 'info';
						title = `Responder dispatched | Report #${data.id}`;
						if (responderLabel) {
							text = `${responderLabel} is on the way.`;
						} else {
							text = 'Responder is on the way.';
						}
					} else if (data.status == "in_progress") {
						icon = 'info';
						title = `Responder in action | Report #${data.id}`;
						if (responderLabel) {
							text = `${responderLabel} is handling the emergency.`;
						} else {
							text = 'Responder is handling the emergency.';
						}
					} else if (data.status == "resolved") {
						icon = 'success';
						title = `Report resolved | Report #${data.id}`;
						if (responderLabel) {
							text = `${responderLabel} has resolved the report.`;
						} else {
							text = 'Report has been resolved.';
						}
					} else if (data.status == "cancelled") {
						icon = 'error';
						title = `Report cancelled | Report #${data.id}`;
						if (responderLabel) {
							text = `${responderLabel} has cancelled the response.`;
						} else {
							text = 'Response has been cancelled.';
						}
					} else {
						// Default notification
						text = 'Report location has been updated.';
					}

					// Show notification to report owner
					if (userId === data.user_id) {
						// Add to notification list
						addNotification(title, text, icon, data.id);
						
						Swal.fire({
							toast: true,
							position: 'top-end',
							icon: icon,
							title: title,
							text: text,
							showConfirmButton: false,
							showCloseButton: true,
							timer: 5000,
							timerProgressBar: true
						});

						// Add click event to the toast element to redirect to map page
						setTimeout(() => {
							const toastElement = document.querySelector('.swal2-toast');
							if (toastElement) {
								toastElement.style.cursor = 'pointer';
								toastElement.addEventListener('click', function(e) {
									// Don't redirect if clicking the close button
									if (!e.target.closest('.swal2-close')) {
										Swal.close();
										window.location.href = "{{ route('reports.map') }}?report_id=" + data.id;
									}
								});
							}
						}, 100);
					}
				@endif

                // Only process map updates if map is defined (i.e., we're on the map page)
                if (typeof map === 'undefined' || !map) return;

				removeAllRoutes();
				
				// Remove responder markers when cancelled
				if (data.status === 'cancelled' || data.cancelled_responders || data.cancelled_responder_id) {
					if (data.cancelled_responders === 'all') {
						// Primary cancelled - remove all responder markers for this report
						if (typeof responderMarkers !== 'undefined') {
							for (const markerKey in responderMarkers) {
								if (markerKey.startsWith(data.id + '-')) {
									const responderMarker = responderMarkers[markerKey];
									if (map.hasLayer(responderMarker)) {
										map.removeLayer(responderMarker);
									}
									delete responderMarkers[markerKey];
								}
							}
						}
					} else if (data.cancelled_responder_id) {
						// Secondary cancelled - remove only this responder's marker
						const markerKey = `${data.id}-${data.cancelled_responder_id}`;
						if (typeof responderMarkers !== 'undefined' && responderMarkers[markerKey]) {
							const responderMarker = responderMarkers[markerKey];
							if (map.hasLayer(responderMarker)) {
								map.removeLayer(responderMarker);
							}
							delete responderMarkers[markerKey];
						}
					}
				}
				
				// Handle deletion
				if (data.deleted === true || data.action === 'delete') {
					removeReportMarker(data.id);
					updateReportData(data); // Remove from reports array
					return;
				}
				
				// Remove report marker if report is resolved or cancelled (is_cancelled = 1)
				if (data.status === 'resolved' || data.is_cancelled === true || data.is_cancelled === 1 || (data.status === 'cancelled' && data.cancelled_responders === 'all')) {
					removeReportMarker(data.id);
					updateReportData(data); // Remove from reports array
					return;
				}

				// Update or add report marker without removing responder markers
				if (reportMarkers[data.id]) {
					// Update existing marker
					const existingMarker = reportMarkers[data.id];
					if (data.latitude && data.longitude) {
						const newLatLng = L.latLng(data.latitude, data.longitude);
						existingMarker.marker.setLatLng(newLatLng);
						existingMarker.pulse.setLatLng(newLatLng);
						
						// Update icon if type or severity changed
						const severity = (data.severity_level || "moderate").toLowerCase();
						const type = (data.type || "others").toLowerCase();
						const oldSeverity = (existingMarker.report.severity_level || "moderate").toLowerCase();
						const oldType = (existingMarker.report.type || "others").toLowerCase();
						
						if (severity !== oldSeverity || type !== oldType) {
							// Update marker icon
							existingMarker.marker.setIcon(createCustomMarker(type, severity));
							
							// Update pulse color
							map.removeLayer(existingMarker.pulse);
							const newPulse = L.marker([data.latitude, data.longitude], {
								icon: L.divIcon({
									className: "",
									html: `<div class="pulse ${severity}"></div>`,
									iconSize: [55, 55],
									iconAnchor: [27.5, 45],
								}),
								interactive: false,
								zIndexOffset: -100,
							}).addTo(map);
							existingMarker.pulse = newPulse;
							
							// Update tooltip
							const tooltipContent = `
								<strong>${data.title || "Emergency Report"}</strong><br>
								Type: ${data.type || "Unknown"}<br>
								Severity: ${data.severity_level || "Moderate"}<br>
								${data.location ? `Location: ${data.location}` : ""}
							`;
							existingMarker.marker.bindTooltip(tooltipContent, {
								permanent: false,
								direction: 'bottom',
								offset: [3, 1],
							});
						}
						
						existingMarker.report = data;
					}
				} else {
					// Add new marker
					addReportMarker(data);
				}
				updateReportData(data);
			});
        });
        @endif
    </script>
    @stack('scripts')
</body>

</html>