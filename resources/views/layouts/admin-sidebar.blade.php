<!-- Sidebar -->
<div class="admin-sidebar" id="adminSidebar">
    <div class="sidebar-header">
        <h5 class="mb-0">
            <i class="bi bi-{{ Auth::user()->isAdmin() ? 'shield-check' : (Auth::user()->isResponder() ? 'person-badge' : 'person') }} me-2"></i>
            {{ Auth::user()->isAdmin() ? 'Admin Panel' : (Auth::user()->isResponder() ? 'Responder Panel' : 'User Panel') }}
        </h5>
        <button class="btn btn-sm btn-link d-md-none sidebar-toggle-close" onclick="toggleSidebar()">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    <nav class="sidebar-nav">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                    <i class="bi bi-speedometer2 me-2"></i> Dashboard
                </a>
            </li>
            <!-- <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('reports.map') ? 'active' : '' }}" href="{{ route('reports.map') }}">
                    <i class="bi bi-map me-2"></i> Map View
                </a>
            </li> -->
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('reports.history*') ? 'active' : '' }}" href="{{ route('reports.history') }}">
                    <i class="bi bi-clock-history me-2"></i> Report History
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('messages*') ? 'active' : '' }}" href="{{ route('messages.index') }}">
                    <i class="bi bi-chat-dots me-2"></i> Messages
                </a>
            </li>
            @if(Auth::user()->isAdmin())
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.users*') ? 'active' : '' }}" href="{{ route('admin.users') }}">
                    <i class="bi bi-people me-2"></i> User Management
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.emergency-types*') ? 'active' : '' }}" href="{{ route('admin.emergency-types') }}">
                    <i class="bi bi-exclamation-triangle me-2"></i> Emergency Types
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.reports*') ? 'active' : '' }}" href="{{ route('admin.reports') }}">
                    <i class="bi bi-file-earmark-text me-2"></i> All Reports
                </a>
            </li>
            @endif
            @if(Auth::user()->isAdmin() || Auth::user()->isResponder())
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('resources*') ? 'active' : '' }}" href="{{ route('resources.index') }}">
                    <i class="bi bi-truck me-2"></i> Resources
                </a>
            </li>
            @endif
            @if(Auth::user()->isAdmin())
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('analytics*') ? 'active' : '' }}" href="{{ route('analytics.index') }}">
                    <i class="bi bi-graph-up me-2"></i> Analytics
                </a>
            </li>
            @endif
        </ul>
    </nav>
</div>

<!-- Mobile Sidebar Toggle Button -->
<button class="btn btn-primary btn-sm d-md-none sidebar-toggle-btn" onclick="toggleSidebar()" style="position: fixed; top: 90px; right: 15px; z-index: 1001; display: none;">
    <i class="bi bi-list"></i>
</button>

<style>
.admin-sidebar {
    position: fixed;
    top: 70px;
    left: 0;
    width: 250px;
    height: calc(100vh - 70px);
    background-color: #fff;
    border-right: 1px solid #dee2e6;
    box-shadow: 2px 0 5px rgba(0,0,0,0.1);
    z-index: 1000;
    overflow-y: auto;
}

.sidebar-header {
    padding: 1.5rem 1rem;
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.sidebar-header h5 {
    color: #495057;
    font-weight: 600;
    margin: 0;
}

.sidebar-toggle-close {
    color: #6c757d;
    padding: 0;
    font-size: 1.2rem;
}

.sidebar-nav {
    padding: 1rem 0;
}

.sidebar-nav .nav-link {
    padding: 0.75rem 1.5rem;
    color: #495057;
    text-decoration: none;
    display: flex;
    align-items: center;
    transition: all 0.3s ease;
    border-left: 3px solid transparent;
}

.sidebar-nav .nav-link:hover {
    background-color: #f8f9fa;
    color: #0d6efd;
    border-left-color: #0d6efd;
}

.sidebar-nav .nav-link.active {
    background-color: #e7f1ff;
    color: #0d6efd;
    border-left-color: #0d6efd;
    font-weight: 600;
}

.sidebar-nav .nav-link i {
    width: 20px;
    text-align: center;
}

@media (max-width: 768px) {
    .admin-sidebar {
        transform: translateX(-100%);
        transition: transform 0.3s ease;
        top: 66px !important;
    }
    
    .admin-sidebar.show {
        transform: translateX(0);
    }
    
    .sidebar-toggle-btn {
        display: block !important;
    }
}
</style>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('adminSidebar');
    sidebar.classList.toggle('show');
}

// Hide sidebar when clicking outside on mobile
document.addEventListener('click', function(event) {
    const sidebar = document.getElementById('adminSidebar');
    const toggleBtn = document.querySelector('.sidebar-toggle-btn');
    
    if (window.innerWidth <= 768) {
        if (!sidebar.contains(event.target) && !toggleBtn.contains(event.target)) {
            sidebar.classList.remove('show');
        }
    }
});
</script>

