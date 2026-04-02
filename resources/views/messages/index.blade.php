@extends('layouts.app')

@section('title', 'Messages')

@section('content')
<div class="container-fluid py-4" style="height: calc(100vh - 140px);">
    <div class="row mb-3">
        <div class="col-12">
            <h2 class="mb-0"><i class="bi bi-chat-dots me-2"></i>Messages</h2>
            <p class="text-muted mb-0">View and manage messages from all your reports</p>
        </div>
    </div>

    <div class="row h-100" style="height: calc(100% - 80px);">
        <!-- Left Sidebar - Reports List -->
        <div class="col-md-4 col-lg-3 messages-sidebar-left" id="reportsSidebar">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-list-ul me-2"></i>Reports</h6>
                </div>
                <div class="card-body p-0" style="overflow-y: auto; max-height: calc(100vh - 220px);">
                    @if($reports->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($reports as $report)
                                @php
                                    $latestMessage = $report->latest_message ?? null;
                                @endphp
                                <a href="javascript:void(0)" 
                                   class="list-group-item list-group-item-action report-item {{ $loop->first ? 'active' : '' }}" 
                                   data-report-id="{{ $report->id }}"
                                   data-unread-count="{{ $report->unread_count }}"
                                   data-latest-message-time="{{ $latestMessage ? $latestMessage->created_at->timestamp : 0 }}">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1" style="color: #000;">Report #{{ $report->id }}</h6>
                                            <small class="text-muted d-block">{{ Str::limit($report->description ?? 'No description', 40) }}</small>
                                            @if($latestMessage)
                                                <small class="text-muted d-block mt-1" style="font-size: 0.75rem;">
                                                    <i class="bi bi-chat-dots"></i> {{ Str::limit($latestMessage->message, 35) }}
                                                </small>
                                            @else
                                                <small class="text-muted d-block mt-1" style="font-size: 0.75rem;">
                                                    <i class="bi bi-chat-dots"></i> No messages yet
                                                </small>
                                            @endif
                                        </div>
                                        @if($report->unread_count > 0)
                                            <span class="badge bg-danger ms-2" id="unreadBadge-{{ $report->id }}">{{ $report->unread_count }}</span>
                                        @else
                                            <span class="badge bg-secondary ms-2" id="unreadBadge-{{ $report->id }}" style="display: none;">0</span>
                                        @endif
                                    </div>
                                    <div class="mt-2">
                                        <span class="badge bg-{{ $report->severity_level === 'critical' ? 'danger' : ($report->severity_level === 'high' ? 'warning' : ($report->severity_level === 'moderate' ? 'info' : 'success')) }} badge-sm">
                                            {{ ucfirst($report->severity_level) }}
                                        </span>
                                        @if($latestMessage)
                                            <small class="text-muted d-block mt-1" style="font-size: 0.7rem;">
                                                {{ $latestMessage->created_at->diffForHumans() }}
                                            </small>
                                        @endif
                                    </div>
                                </a>
                            @endforeach
                        </div>
                        <div class="card-footer bg-white border-top">
                            <div class="text-center">
                                {{ $reports->links() }}
                            </div>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="bi bi-chat-dots" style="font-size: 2rem; color: #dee2e6;"></i>
                            <p class="text-muted mt-2 mb-0">No reports with messages</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Right Side - Messages Display -->
        <div class="col-md-8 col-lg-9 messages-content-right" id="messagesView">
            <div class="card border-0 shadow-sm h-100">
                <div id="messagesHeader" class="card-header bg-white d-flex align-items-center">
                    <button id="backToReportsBtn" class="btn btn-link p-0 me-2 d-md-none" style="display: none; text-decoration: none; color: inherit;">
                        <i class="bi bi-arrow-left" style="font-size: 1.2rem;"></i>
                    </button>
                    <h6 class="mb-0 flex-grow-1"><i class="bi bi-chat-dots me-2"></i>Select a report to view messages</h6>
                </div>
                <div class="card-body p-0 d-flex flex-column" style="height: calc(100vh - 220px);">
                    <div id="messagesContainer" class="flex-grow-1" style="overflow-y: auto; padding: 15px; min-height: 400px;">
                        <div class="text-center py-5">
                            <i class="bi bi-chat-left-text" style="font-size: 3rem; color: #dee2e6;"></i>
                            <p class="text-muted mt-3">Select a report from the left to view messages</p>
                        </div>
                    </div>
                    <div id="messageFormContainer" class="border-top p-3 bg-light" style="display: none;">
                        <form id="messageForm" class="d-flex gap-2 align-items-end">
                            @csrf
                            <input type="hidden" id="currentReportId" value="">
                            <textarea id="messageInput" class="form-control" placeholder="Type a message... (Press Enter for new line, Ctrl+Enter to send)" rows="1" style="resize: none; min-height: 48px; max-height: 100px; height: 50px; overflow-y: auto;" required></textarea>
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="bi bi-send">Send</i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.messages-sidebar-left {
    border-right: 1px solid #dee2e6;
    padding-right: 0;
}

.messages-content-right {
    padding-left: 0;
}

/* Mobile Responsive Styles - Facebook Messenger-like */
@media (max-width: 767.98px) {
    
    .row.mb-3 h2 {
        font-size: 1.25rem;
    }
    
    .row.mb-3 p {
        font-size: 0.875rem;
    }
    
    .messages-sidebar-left {
        position: fixed;
        left: 0;
        width: 100%;
        height: calc(100vh - 160px) !important;
        z-index: 999;
        background: white;
        transform: translateX(0);
        border-right: none;
        padding: 0 !important;
    }
    
    .messages-sidebar-left.hide-on-mobile {
        transform: translateX(-100%);
    }
    
    .messages-content-right {
        position: fixed;
        left: 0;
        width: 100%;
        height: calc(100vh - 160px) !important;
        z-index: 999;
        background: white;
        border-right: none;
        padding: 0 !important;
    }
    
    .messages-content-right .card {
        border-radius: 0;
        height: calc(100vh - 200px) !important;
        margin: 0;
    }
    
    .messages-content-right .card-body {
        height: calc(100vh - 120px) !important;
    }
    
    #messagesContainer {
        padding: 10px !important;
        min-height: auto !important;
    }
    
    #messageFormContainer {
        padding: 10px !important;
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background: white;
        border-top: 1px solid #dee2e6;
        z-index: 100;
    }
    
    #messagesContainer {
        padding-bottom: 30px !important;
    }
    
    .message-item {
        margin-bottom: 10px;
    }
   
    .reader-avatar{
        width: 15px !important;
        height: 15px !important;
    }
     
    .message-sender-avatar {
        width: 40px !important;
        height: 40px !important;
    }

    .message-bubble {
        max-width: 250px !important;
        padding: 8px 20px 8px 10px !important;
        font-size: 0.70rem !important;
    }

    .message-bubble.own {
        margin-left: auto;
        margin-right: 0;
    }

    .message-bubble.other {
        margin-left: 0;
        margin-right: auto;
    }

    .message-time {
        font-size: 0.7rem;
    }
    
    .report-item {
        padding: 12px 15px;
    }

    .message-time-text, .message-status {
        font-size: 0.60rem !important;
    }
    
    .report-item h6 {
        font-size: 0.95rem;
    }
    
    .report-item small {
        font-size: 0.8rem;
    }
    
    #backToReportsBtn {
        display: inline-block !important;
    }
    
    /* Hide messages view initially on mobile */
    .messages-content-right.hide-on-mobile {
        display: none;
    }
    
    /* Show sidebar initially on mobile */
    .messages-sidebar-left.show-on-mobile {
        display: block;
    }

    #messageInput{
        min-height: 40px !important;
        height: 48px !important;
        font-size: 0.70rem !important;
        padding: 10px !important;
    }
}

@media (min-width: 768px) {
    #backToReportsBtn {
        display: none !important;
    }
    
    .messages-sidebar-left.hide-on-mobile {
        transform: none;
    }
    
    .messages-content-right.hide-on-mobile {
        display: block !important;
    }
}

.report-item {
    border-left: 3px solid transparent;
    cursor: pointer;
    transition: all 0.2s ease;
}

.report-item:hover {
    background-color: #f8f9fa;
    border-left-color: #0d6efd;
}

.report-item.active {
    background-color: #e7f1ff;
    border-left-color: #0d6efd;
    font-weight: 500;
}

.message-item {
    margin-bottom: 15px;
    align-items: flex-start !important;
}

.message-item.text-end {
    flex-direction: row-reverse;
}

.message-item.text-start {
    flex-direction: row;
}

.message-sender-avatar {
    width: 40px;
    height: 40px;
    object-fit: cover;
    align-self: flex-end;
    margin-top: 0;
    margin-bottom: 30px;
}

.message-item:has(.message-read-by) .message-sender-avatar {
    margin-bottom: 54px !important;
}

.message-bubble {
    max-width: 500px;
    padding: 10px 15px;
    border-radius: 18px;
    word-wrap: break-word;
}

.message-bubble.own {
    background-color: #0d6efd;
    color: white;
}

.message-bubble.other {
    background-color: #e9ecef;
    color: #212529;
}

.message-time {
    font-size: 0.75rem;
    opacity: 0.7;
    margin-top: 0px;
    padding: 0 4px;
}

.message-status {
    font-size: 0.7rem;
    opacity: 0.8;
}

.message-status.read {
    opacity: 1;
}

.message-status.sent {
    opacity: 0.6;
}
</style>

<script>
// Set initial mobile state immediately
(function() {
    if (window.innerWidth < 768) {
        const sidebar = document.getElementById('reportsSidebar');
        const messagesView = document.getElementById('messagesView');
        if (sidebar) sidebar.classList.remove('hide-on-mobile');
        if (messagesView) messagesView.classList.add('hide-on-mobile');
    }
})();

let currentReportId = null;
let messageSentChannel = null;
let messageReadChannel = null;
// Store read status data from Pusher events
let messageReadStatus = {}; // Format: { messageId: { readers: [...], lastReadAt: timestamp } }
let usersInvolved = [];
// Admin and super admin users should not mark messages as read
const isAdmin = {{ auth()->user()->isAdmin() ? 'true' : 'false' }};

// Mobile navigation helpers
function isMobile() {
    return window.innerWidth < 768;
}

function showMessagesView() {
    if (isMobile()) {
        const sidebar = document.querySelector('.messages-sidebar-left');
        const messagesView = document.querySelector('.messages-content-right');
        if (sidebar) sidebar.classList.add('hide-on-mobile');
        if (messagesView) messagesView.classList.remove('hide-on-mobile');
        const backBtn = document.getElementById('backToReportsBtn');
        if (backBtn) backBtn.style.display = 'inline-block';
    }
}

function showSidebarView() {
    if (isMobile()) {
        const sidebar = document.querySelector('.messages-sidebar-left');
        const messagesView = document.querySelector('.messages-content-right');
        if (sidebar) sidebar.classList.remove('hide-on-mobile');
        if (messagesView) messagesView.classList.add('hide-on-mobile');
        const backBtn = document.getElementById('backToReportsBtn');
        if (backBtn) backBtn.style.display = 'none';
    }
}

// Load messages for a report
async function loadReportMessages(reportId) {
    currentReportId = reportId;
    
    // Update active state
    document.querySelectorAll('.report-item').forEach(item => {
        item.classList.remove('active');
    });
    const reportItem = document.querySelector(`[data-report-id="${reportId}"]`);
    if (reportItem) {
        reportItem.classList.add('active');
    }
    
    // Show messages view on mobile
    showMessagesView();
    
    // Show loading state
    const container = document.getElementById('messagesContainer');
    container.innerHTML = '<div class="text-center py-5"><div class="spinner-border" role="status"></div><p class="mt-2 text-muted">Loading messages...</p></div>';
    
    // Update header
    const header = document.getElementById('messagesHeader');
    const backBtnHtml = isMobile() ? '<button id="backToReportsBtn" class="btn btn-link p-0 me-2" style="display: inline-block; text-decoration: none; color: inherit;"><i class="bi bi-arrow-left" style="font-size: 1.2rem;"></i></button>' : '';
    header.innerHTML = `${backBtnHtml}<h6 class="mb-0 flex-grow-1"><i class="bi bi-chat-dots me-2"></i>Loading Report #${reportId}...</h6>`;
    
    // Re-attach back button event listener
    if (isMobile()) {
        const backBtn = document.getElementById('backToReportsBtn');
        if (backBtn) {
            backBtn.addEventListener('click', function(e) {
                e.preventDefault();
                showSidebarView();
            });
        }
    }
    
    // Show message form
    document.getElementById('messageFormContainer').style.display = 'block';
    document.getElementById('currentReportId').value = reportId;
    
    try {
        const response = await fetch(`/reports/${reportId}/messages/api`);
        const data = await response.json();
        usersInvolved = data.users_involved || [];
        if (data.status === 'success') {
            const report = data.report;
            const reportTitle = `Report #${reportId}`;
            const reportDescription = report.type_name || '';
            const backBtnHtml = isMobile() ? '<button id="backToReportsBtn" class="btn btn-link p-0 me-2" style="display: inline-block; text-decoration: none; color: inherit;"><i class="bi bi-arrow-left" style="font-size: 1.2rem;"></i></button>' : '';
            header.innerHTML = `${backBtnHtml}<h6 class="mb-0 flex-grow-1"><i class="bi bi-chat-dots me-2"></i>${reportTitle} | ${reportDescription}</h6>`;
            
            // Re-attach back button event listener
            if (isMobile()) {
                const backBtn = document.getElementById('backToReportsBtn');
                if (backBtn) {
                    backBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        showSidebarView();
                    });
                }
            }
            
            // Display messages
            displayMessages(data.messages);
            
            // Store read status from loaded messages (for all messages, not just current user's)
            const currentUserId = {{ auth()->id() ?? 'null' }};
            data.messages.forEach(msg => {
                // Store read status for all messages that have been read
                if (msg.read_by && msg.read_by.length > 0) {
                    const readTimestamps = msg.read_by
                        .map(reader => reader.read_at ? new Date(reader.read_at).getTime() : 0)
                        .filter(ts => ts > 0);
                    const maxTimestamp = readTimestamps.length > 0 ? Math.max(...readTimestamps) : Date.now();
                    
                    messageReadStatus[msg.id] = {
                        readers: msg.read_by,
                        lastReadAt: maxTimestamp
                    };
                }
            });
            
            // Show indicator on last read message
            setTimeout(() => {
                showLastReadIndicatorFromPusherData(currentUserId);
            }, 0);
            
            // Update unread count to 0 when viewing messages (they're marked as read)
            updateUnreadCount(reportId, []);
        } else {
            container.innerHTML = '<div class="alert alert-danger">Error loading messages</div>';
        }
    } catch (error) {
        console.error('Error loading messages:', error);
        container.innerHTML = '<div class="alert alert-danger">Failed to load messages. Please try again.</div>';
    }
}

// Refresh messages without full reload
async function refreshMessages(reportId) {
    try {
        const response = await fetch(`/reports/${reportId}/messages/api`);
        const data = await response.json();
        
        if (data.status === 'success' && currentReportId === reportId) {
            displayMessages(data.messages);
            // Messages are automatically marked as read when fetched via getMessages API
            // The read status will be broadcast to other users via Pusher
            return Promise.resolve();
        }
        return Promise.resolve();
    } catch (error) {
        console.error('Error refreshing messages:', error);
        return Promise.reject(error);
    }
}

// Update message read status in the UI
function updateMessageReadStatus(messageId, isRead, readByUserId = null) {
    const messageItem = document.querySelector(`[data-message-id="${messageId}"]`);
    if (messageItem) {
        const statusIcon = messageItem.querySelector('.message-status i');
        const statusSpan = messageItem.querySelector('.message-status');
        const messageUserId = parseInt(messageItem.getAttribute('data-user-id') || '0');
        const currentUserId = {{ auth()->id() ?? 'null' }};
        
        // Only update if this is the current user's message and someone read it
        if (messageUserId === currentUserId && isRead && statusIcon && statusSpan) {
            // Update to read status (double check) - always update to ensure it's current
            statusIcon.className = 'bi bi-check2-all text-primary';
            statusSpan.classList.remove('sent');
            statusSpan.classList.add('read');
            statusSpan.title = 'Read';
        }
    }
}

// Update "Read by" indicator for a specific message - show avatars of readers who read up to this message
// Shows on both left side (other users' messages) and right side (current user's messages)
function updateReadByIndicator(messageId, readersForThisMessage) {
    // Find the message item for the current message
    const messageItem = document.querySelector(`[data-message-id="${messageId}"]`);
    if (!messageItem) {
        return;
    }
    
    // Remove existing indicator for this message if it exists
    const existingIndicator = messageItem.querySelector('.message-read-by');
    if (existingIndicator) {
        existingIndicator.remove();
    }
    
    // Find the message bubble element
    const messageBubble = messageItem.querySelector('.message-bubble');
    if (!messageBubble) {
        return;
    }
    
    // Filter readers based on message side
    const currentUserId = {{ auth()->id() ?? 'null' }};
    const messageUserId = parseInt(messageItem.getAttribute('data-user-id') || '0');
    
    // Filter readers:
    // - Always exclude message sender's avatar
    // - Always exclude current user's avatar (hide own avatar from reader list)
    const filteredReaders = readersForThisMessage.filter(reader => {
        const readerId = reader.user_id || reader.id;
        // Always exclude message sender
        if (readerId === messageUserId) {
            return false;
        }
        // Always exclude current user's avatar
        if (readerId === currentUserId) {
            return false;
        }
        // Include all other readers
        return true;
    });

   
    
    // Only show if there are readers
    if (filteredReaders && filteredReaders.length > 0) {
        // Create the element - place it after the bubble
        const readByElement = document.createElement('div');
        readByElement.className = 'message-read-by';
        // Determine alignment based on message ownership
        const isOwn = messageItem.classList.contains('text-end');
        readByElement.style.cssText = `font-size: 0.7rem; opacity: 0.7; display: flex; align-items: center; gap: 4px; ${isOwn ? 'justify-content: flex-end;' : 'justify-content: flex-start;'}`;
        
        // Create avatars HTML
        const avatarsHtml = filteredReaders.map(reader => {
            const user = usersInvolved.find(user => user.user_id === reader.id || user.user_id === reader.user_id);
            let avatar = '';
            let userName = '';
            if (typeof user !== 'undefined' && user) {
                avatar = user.avatar;
                userName = user.user_name;
            } else {
                avatar = reader.avatar || reader.avatar_url || '';
                userName = reader.user_name || reader.name || 'Unknown';
                
                // Only use fallback if avatar is truly empty (shouldn't happen as API always provides Laravolt Avatar)
                // Use the same fallback generation as backend to ensure consistency
                if (!avatar || avatar.trim() === '') {
                    // Generate fallback avatar matching backend logic
                    const colors = ['#FF6B6B', '#4ECDC4', '#45B7D1', '#FFA07A', '#98D8C8', '#F7DC6F', '#BB8FCE', '#85C1E2'];
                    const colorIndex = userName.charCodeAt(0) % colors.length;
                    const bgColor = colors[colorIndex];
                    const initials = userName.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
                    
                    // Create a simple SVG avatar as data URI (matching backend generateFallbackAvatar)
                    avatar = `data:image/svg+xml,${encodeURIComponent(`<svg width="20" height="20" xmlns="http://www.w3.org/2000/svg"><circle cx="10" cy="10" r="10" fill="${bgColor}"/><text x="10" y="10" font-family="Arial" font-size="10" fill="white" text-anchor="middle" dominant-baseline="central" font-weight="bold">${initials}</text></svg>`)}`;
                }
            }
            
            return `<img src="${avatar}" alt="${escapeHtml(userName)}" width="20" height="20" class="rounded-circle reader-avatar" title="${escapeHtml(userName)}" style="margin-left: 4px;" onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'20\' height=\'20\'%3E%3Ccircle cx=\'20\' cy=\'20\' r=\'20\' fill=\'%23ccc\'/%3E%3Ctext x=\'20\' y=\'20\' font-family=\'Arial\' font-size=\'14\' fill=\'white\' text-anchor=\'middle\' dominant-baseline=\'central\'%3E${encodeURIComponent(userName.charAt(0).toUpperCase())}%3C/text%3E%3C/svg%3E';">`;
        }).join('');
        readByElement.innerHTML = avatarsHtml;
        
        // Find the flex-column container (parent of message bubble) and insert below the bubble
        const flexColumnContainer = messageBubble.parentElement;
        if (flexColumnContainer && flexColumnContainer.classList.contains('d-flex') && flexColumnContainer.classList.contains('flex-column')) {
            // Insert at the end of the flex-column container (below the bubble)
            flexColumnContainer.appendChild(readByElement);
        } else {
            // Fallback: insert after the message bubble
            if (messageBubble.nextSibling) {
                messageItem.insertBefore(readByElement, messageBubble.nextSibling);
            } else {
                messageItem.appendChild(readByElement);
            }
        }
    }
}

// Show read indicators using Pusher data - show each reader's avatar on the latest message they read
function showLastReadIndicatorFromPusherData(currentUserId) {
    // First, remove ALL existing indicators
    document.querySelectorAll('.message-read-by').forEach(el => {
        el.remove();
    });
    
    // Get all message items
    const allMessageItems = Array.from(document.querySelectorAll('.message-item'));
    
    if (allMessageItems.length === 0) {
        return;
    }
    
    // Sort messages by timestamp (most recent first)
    allMessageItems.sort((a, b) => {
        const timestampA = parseFloat(a.getAttribute('data-timestamp') || '0');
        const timestampB = parseFloat(b.getAttribute('data-timestamp') || '0');
        return timestampB - timestampA; // Descending order (most recent first)
    });
    
    // Track which message each reader has read up to
    // Format: { readerId: { messageId, readerData } }
    const readerLastReadMessage = {};
    
    // Process all messages to find the latest message each reader has read
    // Process both left side (other users' messages) and right side (current user's messages)
    for (const messageItem of allMessageItems) {
        const messageId = parseInt(messageItem.getAttribute('data-message-id'));
        
        // Get read status for this message (works for all messages, not just current user's)
        const readStatus = messageReadStatus[messageId];
        
        if (readStatus && readStatus.readers && readStatus.readers.length > 0) {
            // For each reader of this message, track their latest read message
            readStatus.readers.forEach(reader => {
                const readerId = reader.user_id || reader.id;
                
                // If we haven't seen this reader yet, or this message is more recent than their current latest
                if (!readerLastReadMessage[readerId]) {
                    readerLastReadMessage[readerId] = {
                        messageId: messageId,
                        reader: reader
                    };
                }
            });
        }
    }
    
    // Group readers by the message they last read
    // Format: { messageId: [reader1, reader2, ...] }
    const readersByMessage = {};
    
    Object.keys(readerLastReadMessage).forEach(readerId => {
        const { messageId, reader } = readerLastReadMessage[readerId];
        
        // Find the message item to get the sender's user ID
        const messageItem = document.querySelector(`[data-message-id="${messageId}"]`);
        if (!messageItem) return;
        
        const messageUserId = parseInt(messageItem.getAttribute('data-user-id') || '0');
        const readerUserId = reader.user_id || reader.id;
        
        // Don't include reader if they are the message sender
        if (readerUserId === messageUserId) {
            return; // Skip this reader (message sender)
        }
        
        // Don't include current user's avatar (hide own avatar from reader list)
        if (readerUserId === currentUserId) {
            return; // Skip current user
        }
        
        if (!readersByMessage[messageId]) {
            readersByMessage[messageId] = [];
        }
        readersByMessage[messageId].push(reader);
    });
    
    // Show avatars on each message where readers have read up to
    Object.keys(readersByMessage).forEach(messageId => {
        const readers = readersByMessage[messageId];
        if (readers && readers.length > 0) {
            updateReadByIndicator(parseInt(messageId), readers);
        }
    });
}

// Mark message as read via API
async function markMessageAsRead(messageId, reportId) {
    try {
        const response = await fetch(`/messages/${messageId}/read`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });
        
        if (response.ok) {
            const data = await response.json();
            return Promise.resolve(data);
        }
        return Promise.resolve();
    } catch (error) {
        console.error('Error marking message as read:', error);
        return Promise.resolve(); // Don't block if this fails
    }
}

// Add new message to display without full refresh
function addNewMessageToDisplay(message) {
    const container = document.getElementById('messagesContainer');
    const currentUserId = {{ auth()->id() ?? 'null' }};
    const isOwn = message.user_id === currentUserId;
    const date = new Date(message.created_at);
    const messageTimestamp = date.getTime() / 1000;
    
    // Check if message already exists (avoid duplicates)
    const existingMessage = document.querySelector(`[data-message-id="${message.id}"]`);
    if (existingMessage) {
        return; // Message already displayed
    }
    
    // Get sender avatar
    const senderName = message.user?.name || 'Unknown';
    const senderAvatar = generateUserAvatar(senderName, message.user?.avatar);
    
    // Create message HTML
    const messageHtml = `
        <div class="message-item ${isOwn ? 'text-end' : 'text-start'} d-flex align-items-start gap-2" data-message-id="${message.id}" data-user-id="${message.user_id}" data-timestamp="${messageTimestamp}" style="${isOwn ? 'flex-direction: row-reverse;' : ''}">
            <img src="${senderAvatar}" alt="${escapeHtml(senderName)}" class="rounded-circle message-sender-avatar" width="48" height="48" style="flex-shrink: 0;" onerror="this.src='${generateUserAvatar(senderName)}';">
            <div class="d-flex flex-column">
                ${!isOwn ? `<strong class="d-block" style="font-size: 0.70rem; margin-left: 8px; margin-bottom: -4px;">${escapeHtml(senderName)}</strong>` : ''}
                <div class="d-inline-block message-bubble ${isOwn ? 'own text-start' : 'other'}">
                    <div class="d-flex align-items-start">
                        <div class="flex-grow-1">
                            <div>${formatMessage(message.message)}</div>
                        </div>
                    </div>
                </div>
                <div class="message-time d-flex align-items-center ${isOwn ? 'justify-content-end' : 'justify-content-start'} gap-2">
                    <span class="message-time-text">${getTimeAgo(date)}</span>
                    ${isOwn ? `
                        <span class="message-status sent" title="Sent">
                            <i class="bi bi-check2"></i>
                        </span>
                    ` : ''}
                </div>
            </div>
        </div>
    `;
    
    // Append to container
    container.insertAdjacentHTML('beforeend', messageHtml);
    
    // Auto-scroll to bottom
    scrollMessagesToBottom();
    
    // Update message times
    if (typeof updateMessageTimesInsideMessages === 'function') {
        updateMessageTimesInsideMessages();
    }
    
    // If message has been read, store read status (for all messages, not just current user's)
    // Avatars will be shown by showLastReadIndicatorFromPusherData
    if (message.read_by && message.read_by.length > 0) {
        // Store read status
        const readTimestamps = message.read_by
            .map(reader => reader.read_at ? new Date(reader.read_at).getTime() : 0)
            .filter(ts => ts > 0);
        const maxTimestamp = readTimestamps.length > 0 ? Math.max(...readTimestamps) : Date.now();
        
        messageReadStatus[message.id] = {
            readers: message.read_by,
            lastReadAt: maxTimestamp
        };
        
        // Show avatars on latest read message (will be determined by showLastReadIndicatorFromPusherData)
        setTimeout(() => {
            const currentUserId = {{ auth()->id() ?? 'null' }};
            showLastReadIndicatorFromPusherData(currentUserId);
            // Ensure scroll to bottom after indicators are shown
            scrollMessagesToBottom();
        }, 0);
    }
}

// Increment unread count when new message arrives
function incrementUnreadCount(reportId) {
    const reportItem = document.querySelector(`[data-report-id="${reportId}"]`);
    if (reportItem) {
        const badge = document.querySelector(`#unreadBadge-${reportId}`);
        if (badge) {
            const currentCount = parseInt(badge.textContent) || 0;
            const newCount = currentCount + 1;
            badge.textContent = newCount;
            badge.classList.remove('bg-secondary');
            badge.classList.add('bg-danger');
            badge.style.display = 'inline-block';
            reportItem.setAttribute('data-unread-count', newCount.toString());
        } else {
            // Create badge if it doesn't exist
            const badgeContainer = reportItem.querySelector('.d-flex.justify-content-between');
            if (badgeContainer) {
                const newBadge = document.createElement('span');
                newBadge.id = `unreadBadge-${reportId}`;
                newBadge.className = 'badge bg-danger ms-2';
                newBadge.textContent = '1';
                badgeContainer.appendChild(newBadge);
                reportItem.setAttribute('data-unread-count', '1');
            }
        }
        
        
        // Reorder sidebar to move this item to top
        reorderSidebar();
    }
}

// Reorder sidebar items: unread messages first, then by latest message time
function reorderSidebar() {
    const sidebar = document.querySelector('.list-group.list-group-flush');
    if (!sidebar) return;
    
    const items = Array.from(sidebar.querySelectorAll('.report-item'));
    
    // Sort items: unread first, then by latest message time
    items.sort((a, b) => {
        // Get unread count from badge or data attribute
        const aBadge = a.querySelector('.badge.bg-danger');
        const aUnread = aBadge && aBadge.style.display !== 'none' 
            ? parseInt(aBadge.textContent) || parseInt(a.getAttribute('data-unread-count')) || 0 
            : parseInt(a.getAttribute('data-unread-count')) || 0;
        const bBadge = b.querySelector('.badge.bg-danger');
        const bUnread = bBadge && bBadge.style.display !== 'none' 
            ? parseInt(bBadge.textContent) || parseInt(b.getAttribute('data-unread-count')) || 0 
            : parseInt(b.getAttribute('data-unread-count')) || 0;
        
        // Primary sort: unread count (higher first)
        if (aUnread !== bUnread) {
            return bUnread - aUnread;
        }
        
        // Secondary sort: latest message time (most recent first)
        const aTime = parseInt(a.getAttribute('data-latest-message-time')) || 0;
        const bTime = parseInt(b.getAttribute('data-latest-message-time')) || 0;
        
        return bTime - aTime;
    });
    
    // Re-append items in sorted order
    items.forEach(item => sidebar.appendChild(item));
}

// Update sidebar message preview when new message arrives
function updateSidebarMessage(reportId, newMessage) {
    const reportItem = document.querySelector(`[data-report-id="${reportId}"]`);
    if (reportItem) {
        const messagePreview = reportItem.querySelector('.text-muted.d-block.mt-1');
        if (messagePreview) {
            messagePreview.innerHTML = `<i class="bi bi-chat-dots"></i> ${escapeHtml(newMessage.message).substring(0, 35)}${newMessage.message.length > 35 ? '...' : ''}`;
        }
        
        // Update timestamp
        const timeElements = reportItem.querySelectorAll('.text-muted[style*="font-size: 0.7rem"]');
        if (timeElements.length > 0) {
            const messageDate = new Date(newMessage.created_at);
            const timeAgo = getTimeAgo(messageDate);
            timeElements[timeElements.length - 1].textContent = timeAgo;
        }
        
        // Update data attribute with latest message timestamp
        const messageTimestamp = new Date(newMessage.created_at).getTime() / 1000;
        reportItem.setAttribute('data-latest-message-time', messageTimestamp);
        
        // Update unread count badge - only if message is not from current user
        const currentUserId = {{ auth()->id() ?? 'null' }};
        if (newMessage.user_id !== currentUserId) {
            // incrementUnreadCount(reportId);
        }
        
        // Always reorder to keep most recent/unread at top
        reorderSidebar();
    }
}

// Helper function to get "time ago" string
function getTimeAgo(date) {
    const seconds = Math.floor((new Date() - date) / 1000);
    
    // Less than 1 minute: show "now"
    if (seconds < 60) {
        return 'now';
    }
    // 1 minute to 4 hours: show minutes/hours
    else if (seconds < 14400) { // 4 hours = 14400 seconds
        const minutes = Math.floor(seconds / 60);
        if (minutes < 60) {
            return `${minutes} min`;
        } else {
            const hours = Math.floor(minutes / 60);
            return `${hours} hr`;
        }
    }
    // 4 hours or more: show date
    else {
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    }
}

// Update message times in real-time
function updateMessageTimesInsideMessages() {
    document.querySelectorAll('.message-item[data-timestamp]').forEach(item => {
        const timestampAttr = item.getAttribute('data-timestamp');
        if (!timestampAttr) return;
        
        // Handle both Unix timestamp and ISO string
        let messageDate;
        if (timestampAttr.includes('T') || timestampAttr.includes('-')) {
            // ISO string format
            messageDate = new Date(timestampAttr);
        } else {
            // Unix timestamp
            messageDate = new Date(parseInt(timestampAttr) * 1000);
        }
        
        const timeElement = item.querySelector('.message-time-text');
        if (timeElement && messageDate) {
            timeElement.textContent = getTimeAgo(messageDate);
        }
    });
}

// Update unread count when messages are read
function updateUnreadCount(reportId, messageIds) {
    const reportItem = document.querySelector(`[data-report-id="${reportId}"]`);
    if (reportItem) {
        const badge = document.querySelector(`#unreadBadge-${reportId}`);
        if (badge) {
            // If messageIds is empty array, reset to 0 (all messages read)
            if (!messageIds || messageIds.length === 0) {
                badge.textContent = '0';
                badge.classList.remove('bg-danger');
                badge.classList.add('bg-secondary');
                badge.style.display = 'none';
                reportItem.setAttribute('data-unread-count', '0');
            } else {
                // Decrease count by number of messages read
                const currentCount = parseInt(badge.textContent) || 0;
                const newCount = Math.max(0, currentCount - messageIds.length);
                
                if (newCount > 0) {
                    badge.textContent = newCount;
                    badge.classList.remove('bg-secondary');
                    badge.classList.add('bg-danger');
                    badge.style.display = 'inline-block';
                    reportItem.setAttribute('data-unread-count', newCount.toString());
                } else {
                    badge.textContent = '0';
                    badge.classList.remove('bg-danger');
                    badge.classList.add('bg-secondary');
                    badge.style.display = 'none';
                    reportItem.setAttribute('data-unread-count', '0');
                }
            }
            
            // Reorder sidebar after unread count changes
            reorderSidebar();
        }
    }
}

// Display messages in the container
function displayMessages(messages) {
    const container = document.getElementById('messagesContainer');
    const currentUserId = {{ auth()->id() ?? 'null' }};
    
    if (messages.length === 0) {
        container.innerHTML = '<div class="text-center py-5"><i class="bi bi-chat-left-text" style="font-size: 2rem; color: #dee2e6;"></i><p class="text-muted mt-3">No messages yet. Start the conversation!</p></div>';
        return;
    }
    
    // Find the last message (most recent message chronologically)
    const ownMessages = messages.filter(msg => msg.user_id === currentUserId);
    const lastMessage = ownMessages.length > 0 ? ownMessages[ownMessages.length - 1] : null;
    const lastMessageId = lastMessage ? lastMessage.id : null;
    
    
    let html = '';
    messages.forEach(msg => {
        const isOwn = msg.user_id === currentUserId;
        const date = new Date(msg.created_at);
        const formattedDate = date.toLocaleDateString('en-US', { 
            month: 'short', 
            day: 'numeric',
            hour: 'numeric',
            minute: '2-digit',
            hour12: true
        });
        
        // Check if message is read by current user (for own messages) or if anyone read it
        const hasBeenRead = msg.read_by && msg.read_by.length > 0 && msg.read_by.some(reader => (reader.user_id || reader.id) !== msg.user_id);
        const allReaders = msg.read_by || [];
        const lastReader = msg.last_read_by || null;
        
        // Show "Read by" avatars on all messages that have been read
        const isLastMessage = msg.id === lastMessageId;
        
        // Store timestamp for real-time updates
        const messageTimestamp = date.getTime() / 1000; // Unix timestamp
        
        // Determine read status icon
        let readStatusIcon = 'check2'; // Single check for unread
        let readStatusClass = 'sent';
        let readStatusTitle = 'Sent';
        
        if (isOwn) {
            // For own messages: show double check if read by anyone
            if (hasBeenRead) {
                readStatusIcon = 'check2-all text-primary';
                readStatusClass = 'read';
                readStatusTitle = lastReader ? `Read by ${lastReader.user_name}` : 'Read';
            } else {
                readStatusIcon = 'check2';
                readStatusClass = 'sent';
                readStatusTitle = 'Sent';
            }
        }

        // Get sender avatar
        const senderName = msg.user?.name || 'Unknown';
        const senderAvatar = generateUserAvatar(senderName, msg.user?.avatar);
        
        html += `
            <div class="message-item ${isOwn ? 'text-end' : 'text-start'} d-flex align-items-start gap-2" data-message-id="${msg.id}" data-user-id="${msg.user_id}" data-timestamp="${messageTimestamp}" style="${isOwn ? 'flex-direction: row-reverse;' : ''}">
                <img src="${senderAvatar}" alt="${escapeHtml(senderName)}" class="rounded-circle message-sender-avatar" width="48" height="48" style="flex-shrink: 0;" onerror="this.src='${generateUserAvatar(senderName)}';">
                <div class="d-flex flex-column">
                    ${!isOwn ? `<strong class="d-block" style="font-size: 0.70rem; margin-left: 8px; margin-bottom: -4px;">${escapeHtml(senderName)}</strong>` : ''}
                    <div class="d-inline-block message-bubble ${isOwn ? 'own text-start' : 'other'}">
                        <div class="d-flex align-items-start">
                            <div class="flex-grow-1">
                                <div>${formatMessage(msg.message)}</div>
                            </div>
                        </div>
                    </div>
                    <div class="message-time d-flex align-items-center ${isOwn ? 'justify-content-end' : 'justify-content-start'} gap-2">
                        <span class="message-time-text">${getTimeAgo(date)}</span>
                        ${isOwn ? `
                            <span class="message-status ${readStatusClass}" title="${readStatusTitle}">
                                <i class="bi bi-${readStatusIcon}"></i>
                            </span>
                        ` : ''}
                    </div>
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
    
    // Auto-scroll to bottom
    scrollMessagesToBottom();
    
    // Update message times immediately
    if (typeof updateMessageTimesInsideMessages === 'function') {
        updateMessageTimesInsideMessages();
    }
    
    // Store read status from message data (for all messages, not just current user's)
    messages.forEach(msg => {
        // Store read status for all messages that have been read
        if (msg.read_by && msg.read_by.length > 0) {
            const readTimestamps = msg.read_by
                .map(reader => reader.read_at ? new Date(reader.read_at).getTime() : 0)
                .filter(ts => ts > 0);
            const maxTimestamp = readTimestamps.length > 0 ? Math.max(...readTimestamps) : Date.now();
            
            messageReadStatus[msg.id] = {
                readers: msg.read_by,
                lastReadAt: maxTimestamp
            };
        }
    });
    
    // Show indicator using stored Pusher data
    setTimeout(() => {
        showLastReadIndicatorFromPusherData(currentUserId);
        // Ensure scroll to bottom after indicators are shown
        scrollMessagesToBottom();
    }, 0);
}

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

// Format message with newlines preserved
function formatMessage(message) {
    if (!message) return '';
    // First escape HTML to prevent XSS
    const escaped = escapeHtml(message);
    // Then replace newlines with <br> tags
    return escaped.replace(/\n/g, '<br>');
}

// Generate avatar for a user (helper function)
function generateUserAvatar(userName, userAvatar = null) {
    if (userAvatar && userAvatar.trim() !== '') {
        return userAvatar;
    }
    
    // Generate a simple colored avatar based on user name
    const colors = ['#FF6B6B', '#4ECDC4', '#45B7D1', '#FFA07A', '#98D8C8', '#F7DC6F', '#BB8FCE', '#85C1E2'];
    const colorIndex = (userName || 'Unknown').charCodeAt(0) % colors.length;
    const bgColor = colors[colorIndex];
    const initials = (userName || 'Unknown').split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
    
    // Create a simple SVG avatar as data URI
    return `data:image/svg+xml,${encodeURIComponent(`<svg width="40" height="40" xmlns="http://www.w3.org/2000/svg"><circle cx="20" cy="20" r="20" fill="${bgColor}"/><text x="20" y="20" font-family="Arial" font-size="14" fill="white" text-anchor="middle" dominant-baseline="central" font-weight="bold">${initials}</text></svg>`)}`;
}

// Auto-scroll messages container to bottom
function scrollMessagesToBottom() {
    const container = document.getElementById('messagesContainer');
    if (container) {
        // Use setTimeout to ensure DOM is fully updated before scrolling
        setTimeout(() => {
            container.scrollTop = container.scrollHeight;
        }, 100);
    }
}

// Auto-resize textarea
function autoResizeTextarea(textarea) {
    textarea.style.height = 'auto';
    const newHeight = Math.min(textarea.scrollHeight, 100); // Max height 120px
    textarea.style.height = (newHeight+5) + 'px';
    textarea.scrollTop = textarea.scrollHeight;

    scrollMessagesToBottom();
}

// Handle Enter key in textarea - allow new lines, submit with Ctrl+Enter or Shift+Enter
document.getElementById('messageInput').addEventListener('keydown', function(e) {
    // Allow Enter to create new line
    if (e.key === 'Enter' && !e.shiftKey && !e.ctrlKey && !e.metaKey) {
        // Prevent default Enter behavior (form submission)
        e.preventDefault();
        // Insert new line at cursor position
        const start = this.selectionStart;
        const end = this.selectionEnd;
        const value = this.value;
        this.value = value.substring(0, start) + '\n' + value.substring(end);
        this.selectionStart = this.selectionEnd = start + 1;
        // Auto-resize
        autoResizeTextarea(this);
    }
    // Submit with Ctrl+Enter or Shift+Enter
    else if (e.key === 'Enter' && (e.ctrlKey || e.shiftKey || e.metaKey)) {
        e.preventDefault();
        document.getElementById('messageForm').dispatchEvent(new Event('submit'));
    }
});

// Auto-resize textarea on input
document.getElementById('messageInput').addEventListener('input', function() {
    autoResizeTextarea(this);
});

// Handle message form submission
document.getElementById('messageForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const input = document.getElementById('messageInput');
    const message = input.value.trim();
    const reportId = document.getElementById('currentReportId').value;
    
    if (!message || !reportId) return;
    
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalHtml = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
    
    try {
        const response = await fetch(`/reports/${reportId}/messages`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ message })
        });
        
        const data = await response.json();
        if (data.status === 'success') {
            input.value = '';
            input.style.height = '38px'; // Reset height
            refreshMessages(reportId);
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message || 'Failed to send message'
            });
        }
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'An error occurred while sending the message'
        });
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalHtml;
    }
});

// Subscribe to global Pusher channels on page load
document.addEventListener('DOMContentLoaded', function() {
    if (typeof pusher === 'undefined') {
        console.error('Pusher is not defined');
        return;
    }
    
    const currentUserId = {{ auth()->id() ?? 'null' }};
    
    // Subscribe to message.sent channel
    try {
        messageSentChannel = pusher.subscribe('message.sent');
        
        messageSentChannel.bind('message.sent', function(eventData) {
            if (eventData.message) {
                const message = eventData.message;
                const reportId = message.emergency_report_id;
                
                if (!reportId) {
                    console.error('Message missing emergency_report_id', message);
                    return;
                }
                
                // Only process if message is not from current user
                if (message.user_id !== currentUserId) {
                    // If receiver is viewing this report, automatically mark the message as read (unless admin)
                    if (parseInt(currentReportId) === parseInt(reportId)) {
                        // Automatically mark the new message as read (admin users skip this)
                        markMessageAsRead(message.id, reportId).then(() => {
                            // Add new message to the display after marking as read
                            addNewMessageToDisplay(message);
                            // Show read indicator on last read message using Pusher data
                            setTimeout(() => {
                                showLastReadIndicatorFromPusherData(currentUserId);
                            }, 0);
                        });
                    } else {
                        // Receiver not viewing - increment unread count
                        incrementUnreadCount(reportId);
                    }
                    
                    // Update sidebar message preview
                    updateSidebarMessage(reportId, message);
                    
                    // Trigger navigation bar message dropdown update
                    setTimeout(() => {
                        if (typeof window.loadRecentMessages === 'function') {
                            window.loadRecentMessages();
                        }
                        if (typeof window.loadUnreadMessageCount === 'function') {
                            window.loadUnreadMessageCount();
                        }
                    }, 100);
                } else {
                    // Message from current user - add to display and show read indicator
                    if (parseInt(currentReportId) === parseInt(reportId)) {
                        // Add new message to the display without full refresh
                        addNewMessageToDisplay(message);
                        // Show read indicator on last read message using Pusher data
                        setTimeout(() => {
                            showLastReadIndicatorFromPusherData(currentUserId);
                        }, 0);
                    }
                    // Update sidebar preview
                    updateSidebarMessage(reportId, message);
                }
            }
        });
    } catch (error) {
        console.error('Error subscribing to message.sent channel:', error);
    }
    
    // Subscribe to message.read channel
    try {
        messageReadChannel = pusher.subscribe('message.read');
        
        messageReadChannel.bind('message.read', function(eventData) {
            if (eventData.messageIds && Array.isArray(eventData.messageIds) && eventData.reportId) {
                const reportId = eventData.reportId;
                const currentUserId = {{ auth()->id() ?? 'null' }};
                const readerName = eventData.readByUserName || 'Someone';

                // Store read status data from Pusher (no fetch needed)
                if (eventData.allReaders && Object.keys(eventData.allReaders).length > 0) {
                    Object.keys(eventData.allReaders).forEach(msgId => {
                        const readers = eventData.allReaders[msgId];
                        if (readers && readers.length > 0) {
                            // Find the most recent read_at timestamp
                            const readTimestamps = readers
                                .map(reader => reader.read_at ? new Date(reader.read_at).getTime() : 0)
                                .filter(ts => ts > 0);
                            const maxTimestamp = readTimestamps.length > 0 ? Math.max(...readTimestamps) : Date.now();
                            
                            messageReadStatus[msgId] = {
                                readers: readers,
                                lastReadAt: maxTimestamp
                            };
                        }
                    });
                }
               
                // Only update read status if viewing this report
                if (parseInt(currentReportId) === parseInt(reportId)) {
                    eventData.messageIds.forEach(messageId => {
                        const messageElement = document.querySelector(`[data-message-id="${messageId}"]`);
                        if (messageElement) {
                            const messageUserId = parseInt(messageElement.getAttribute('data-user-id') || '0');
                            let isRead = messageReadStatus[messageId]?.readers?.some(reader => 
                                (reader.user_id || reader.id) !== messageUserId
                            ) || false;
                            // If current user read it, mark as read
                            if (eventData.readByUserId === currentUserId) {
                                updateMessageReadStatus(messageId, isRead, eventData.readByUserId);
                            }
                            // If current user sent the message and someone else read it, update seen status
                            else if (messageUserId === currentUserId) {
                                updateMessageReadStatus(messageId, isRead, eventData.readByUserId);
                            }
                        }
                    });
                    
                    // Show avatars on all read messages using stored Pusher data (no fetch)
                    showLastReadIndicatorFromPusherData(currentUserId);
                }
                
                // Update unread count in sidebar (only if current user read the messages)
                if (eventData.readByUserId === currentUserId) {
                    updateUnreadCount(reportId, eventData.messageIds);
                }
                
                // Trigger navigation bar updates when messages are read
                setTimeout(() => {
                    if (typeof window.loadRecentMessages === 'function') {
                        window.loadRecentMessages();
                    }
                    if (typeof window.loadUnreadMessageCount === 'function') {
                        window.loadUnreadMessageCount();
                    }
                }, 100);
            }
        });
    } catch (error) {
        console.error('Error subscribing to message.read channel:', error);
    }
    
    // Handle report item clicks
    document.querySelectorAll('.report-item').forEach(item => {
        item.addEventListener('click', function() {
            const reportId = parseInt(this.getAttribute('data-report-id'));
            if (reportId) {
                loadReportMessages(reportId);
                window.history.pushState({}, '', `{{ route("messages.index") }}`);
                sessionStorage.setItem('openReportId', reportId);
                // On mobile, show messages view and hide sidebar
                showMessagesView();
            }
        });
    });
    
    // Handle back button click
    const backBtn = document.getElementById('backToReportsBtn');
    if (backBtn) {
        backBtn.addEventListener('click', function(e) {
            e.preventDefault();
            showSidebarView();
        });
    }
    
    // Initialize mobile view state
    if (isMobile()) {
        // If no report is selected, show sidebar
        if (!currentReportId) {
            showSidebarView();
        } else {
            // If report is selected, show messages
            showMessagesView();
        }
    }
    
    // Handle window resize
    window.addEventListener('resize', function() {
        if (!isMobile()) {
            // On desktop, show both views
            const sidebar = document.querySelector('.messages-sidebar-left');
            const messagesView = document.querySelector('.messages-content-right');
            if (sidebar) sidebar.classList.remove('hide-on-mobile');
            if (messagesView) messagesView.classList.remove('hide-on-mobile');
        }
    });
    
    // Check if report_id is in URL query string OR sessionStorage (from notification click)
    const urlParams = new URLSearchParams(window.location.search);
    const reportIdFromUrl = urlParams.get('report_id');
    const reportIdFromStorage = sessionStorage.getItem('openReportId');
    
    // Use URL parameter first, then sessionStorage, then default to first report
    const reportIdToLoad = reportIdFromUrl || reportIdFromStorage;
    
    if (reportIdToLoad) {
        // Clear sessionStorage after reading it
        if (reportIdFromStorage) {
            sessionStorage.removeItem('openReportId');
        }
        
        // Load the specific report
        const reportItem = document.querySelector(`[data-report-id="${reportIdToLoad}"]`);
        if (reportItem) {
            // Mark as active
            document.querySelectorAll('.report-item').forEach(item => {
                item.classList.remove('active');
            });
            reportItem.classList.add('active');
            
            // Load messages for this report
            loadReportMessages(parseInt(reportIdToLoad));
            // On mobile, show messages view
            if (isMobile()) {
                showMessagesView();
            }
        } else {
            // Report not in current page, try to load it anyway
            loadReportMessages(parseInt(reportIdToLoad));
            if (isMobile()) {
                showMessagesView();
            }
        }
    } else {
        // On mobile, show sidebar if no report selected
        if (isMobile()) {
            showSidebarView();
        }
        // Load first report if no specific report requested (desktop only)
        if (!isMobile()) {
            const firstReport = document.querySelector('.report-item');
            if (firstReport) {
                const reportId = firstReport.getAttribute('data-report-id');
                if (reportId) {
                    loadReportMessages(parseInt(reportId));
                }
            }
        }
    }
    
    // Update message times every second for real-time updates
    setInterval(() => {
        if (typeof updateMessageTimesInsideMessages === 'function') {
            updateMessageTimesInsideMessages();
        }
    }, 1000);
    
});
</script>
@endsection

