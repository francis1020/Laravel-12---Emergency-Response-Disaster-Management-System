@extends('layouts.app')

@section('title', 'Report Messages')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <a href="{{ route('reports.show', $report->id) }}" class="btn btn-outline-secondary btn-sm mb-2">
                        <i class="bi bi-arrow-left me-2"></i>Back to Report
                    </a>
                    <h2 class="mb-0">Messages - Report #{{ $report->id }}</h2>
                    <p class="text-muted mb-0">Chat and communication for this emergency report</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-chat-dots me-2"></i>Messages</h5>
                </div>
                <div class="card-body">
                    <div id="messagesContainer" style="max-height: 600px; overflow-y: auto; margin-bottom: 15px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                        <div class="text-center">
                            <div class="spinner-border spinner-border-sm" role="status"></div>
                        </div>
                    </div>
                    <form id="messageForm" class="d-flex gap-2">
                        @csrf
                        <input type="text" id="messageInput" class="form-control" placeholder="Type a message..." required>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-send"></i> Send
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const reportId = {{ $report->id }};

// Load messages on page load
document.addEventListener('DOMContentLoaded', function() {
    loadMessages();
    
    // Auto-refresh messages every 5 seconds
    setInterval(loadMessages, 5000);
    
    // Listen for real-time message updates via Pusher
    if (typeof pusher !== 'undefined') {
        const channel = pusher.subscribe('report.' + reportId);
        channel.bind('message.sent', function(data) {
            loadMessages();
        });
    }
});

// Message Functions
async function loadMessages() {
    try {
        const response = await fetch(`/reports/${reportId}/messages/api`);
        const data = await response.json();
        const container = document.getElementById('messagesContainer');
        
        if (data.status === 'success') {
            if (data.messages.length === 0) {
                container.innerHTML = '<p class="text-muted text-center mb-0">No messages yet. Start the conversation!</p>';
                return;
            }

            let html = '';
            data.messages.forEach(msg => {
                const isOwn = msg.user_id === {{ auth()->id() }};
                const date = new Date(msg.created_at);
                const formattedDate = date.toLocaleDateString('en-US', { 
                    month: 'short', 
                    day: 'numeric', 
                    year: 'numeric',
                    hour: 'numeric',
                    minute: '2-digit',
                    hour12: true
                });
                
                html += `
                    <div class="mb-3 ${isOwn ? 'text-end' : ''}">
                        <div class="d-inline-block p-3 rounded ${isOwn ? 'bg-primary text-white' : 'bg-white border'}" style="max-width: 70%;">
                            <small class="d-block ${isOwn ? 'text-white-50' : 'text-muted'} mb-1">
                                <strong>${msg.user.name}</strong> - ${formattedDate}
                            </small>
                            <div class="${isOwn ? 'text-white' : ''}">${escapeHtml(msg.message)}</div>
                        </div>
                    </div>
                `;
            });
            container.innerHTML = html;
            container.scrollTop = container.scrollHeight;
        }
    } catch (error) {
        console.error('Error loading messages:', error);
        const container = document.getElementById('messagesContainer');
        container.innerHTML = '<p class="text-danger text-center mb-0">Error loading messages. Please refresh the page.</p>';
    }
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

document.getElementById('messageForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const input = document.getElementById('messageInput');
    const message = input.value.trim();
    
    if (!message) return;
    
    const submitBtn = this.querySelector('button[type="submit"]');
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
            loadMessages();
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
        submitBtn.innerHTML = '<i class="bi bi-send"></i> Send';
    }
});
</script>
@endsection

