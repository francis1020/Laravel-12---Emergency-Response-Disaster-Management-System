<header class="navigation sticky-top">
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container-fluid">
            <div class="row w-100 align-items-center">

                {{-- Left: Logo --}}
                <div class="col-md-3 col-sm-3 col-3 d-flex align-items-center">
                    <a class="navbar-brand d-flex align-items-center" href="/">
                        <img src="{{ asset('assets/img/logo2.png') }}" alt="Emergency Response System" height="40">
                        <h2 class="mt-1 title ms-2 mb-0">Emergency Response System</h2>
                    </a>
                </div>

                {{-- Center: Time and Date --}}
                <div class="col-md-6 col-sm-6 col-6 text-center">
                    <div class="time_and_date fw-semibold" id="time_and_date"></div>
                </div>

                {{-- Right: Messages, Notifications, Avatar --}}
                <div class="col-md-3 col-sm-3 col-3 d-flex justify-content-end align-items-center gap-2">
                    @if(Auth::check())
                        {{-- Messages Dropdown --}}
                        <div class="dropdown">
                            <a class="position-relative text-decoration-none" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false" style="color: #333; font-size: 1.5rem;">
                                <i class="bi bi-chat-dots"></i>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="messageBadge" style="display: none; font-size: 0.65rem;">
                                    0
                                </span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" style="min-width: 320px; max-height: 400px; overflow-y: auto; left: 20%; transform: translateX(-80%);" >
                                <li><h6 class="dropdown-header d-flex justify-content-between align-items-center">
                                    <span>Messages</span>
                                    <a href="{{ route('messages.index') }}" class="btn btn-sm btn-link p-0" style="font-size: 0.75rem;">View All</a>
                                </h6></li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <div id="messagesList" class="px-3 py-2 text-muted text-center">
                                        <small>Loading messages...</small>
                                    </div>
                                </li>
                            </ul>
                        </div>

                        {{-- Notifications Dropdown --}}
                        <div class="dropdown">
                            <a class="position-relative text-decoration-none" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false" style="color: #333; font-size: 1.5rem;">
                                <i class="bi bi-bell"></i>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="notificationBadge" style="display: none; font-size: 0.65rem;">
                                    0
                                </span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" style="min-width: 300px; max-height: 400px; overflow-y: auto;">
                                <li><h6 class="dropdown-header d-flex justify-content-between align-items-center">
                                    <span>Notifications</span>
                                    <button id="markAllNotificationsRead" class="btn btn-sm btn-link p-0 text-decoration-none" style="font-size: 0.75rem; border: none; background: none; color: #0d6efd;" title="Mark all as read">
                                        Read All
                                    </button>
                                </h6></li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <div id="notificationsList" class="px-3 py-2 text-muted text-center">
                                        <small>No notifications</small>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    @endif

                    <div class="dropdown" style="margin-right: -25px;">
                        @if(Auth::check())
                            <a type="button" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false">
                                @php
                                    $avatar = \Laravolt\Avatar\Facade::create(Auth::user()->name)->toBase64();
                                @endphp
                                <img src="{{ $avatar }}" alt="{{ Auth::user()->name }}" width="40" height="40" class="rounded-circle">
                            </a>
                            <ul class="dropdown-menu dropdown-menu-lg-end">
                                <li>
                                    <a class="dropdown-item" href="{{ route('dashboard') }}">
                                        <i class="bi bi-speedometer2 me-2"></i> Dashboard
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('reports.map') }}">
                                        <i class="bi bi-map me-2"></i> Map View
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('reports.history') }}">
                                        <i class="bi bi-clock-history me-2"></i> Report History
                                    </a>
                                </li>
                                @if(Auth::user()->isAdmin() || Auth::user()->isResponder())
                                <li>
                                    <a class="dropdown-item" href="{{ route('resources.index') }}">
                                        <i class="bi bi-truck me-2"></i> Resources
                                    </a>
                                </li>
                                @endif
                                @if(Auth::user()->isAdmin())
                                <li>
                                    <a class="dropdown-item" href="{{ route('analytics.index') }}">
                                        <i class="bi bi-graph-up me-2"></i> Analytics
                                    </a>
                                </li>
                                @endif
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('profile.edit') }}">
                                        <i class="bi bi-person-circle me-2"></i> {{ __('Profile') }}
                                    </a>
                                </li>
                                <li>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <a class="dropdown-item" href="{{ route('logout') }}"
                                            onclick="event.preventDefault(); this.closest('form').submit();">
                                            <i class="bi bi-box-arrow-right me-2"></i> {{ __('Log Out') }}
                                        </a>
                                    </form>
                                </li>
                            </ul>
                        @else
                            @if(request()->routeIs('login'))
                                <a class="btn btn-success btn-lg login_btn" href="{{ route('register') }}">Register</a>
                            @else
                                <a class="btn btn-success btn-lg login_btn" href="{{ route('login') }}">Login</a>
                            @endif
                        @endif
                    </div>
                </div>

            </div>
        </div>
    </nav>
</header>