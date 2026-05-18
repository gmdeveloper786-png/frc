<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'FRC Management') | {{ $frc['organisation_name'] ?? 'Faizan Rehabilitation Centre' }}</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="{{ asset('css/frc.css') }}">
<script>
(function () {
    try {
        if (localStorage.getItem('frc-sidebar-collapsed') === '1') {
            document.documentElement.classList.add('sidebar-collapsed');
        }
    } catch (e) {}
})();
</script>
@stack('styles')
</head>
<body>

{{-- ── Sidebar ────────────────────────────────────────────────────────────── --}}
<aside class="frc-sidebar" id="frcSidebar">
    <div class="sidebar-brand">
        <div class="sidebar-brand-icon">
            <i class="fa-solid fa-hands-holding-child"></i>
        </div>
        <div class="sidebar-brand-text">
            <div class="brand-text">{{ $frc['organisation_short_name'] ?? 'Faizan Rehab' }}</div>
            <div class="brand-sub">{{ $frc['organisation_tagline'] ?? 'Management System' }}</div>
        </div>
    </div>

    <div class="sidebar-nav-scroll">
    <nav>
        @php $role = auth()->user()?->role?->name; @endphp

        @if($role === 'super_admin')
            @include('partials.sidebar.super-admin')
        @elseif($role === 'admin')
            @include('partials.sidebar.admin')
        @elseif($role === 'therapist')
            @include('partials.sidebar.therapist')
        @elseif($role === 'finance')
            @include('partials.sidebar.finance')
        @elseif($role === 'child')
            @include('partials.sidebar.child')
        @endif
    </nav>
    </div>

    <div class="sidebar-footer">
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="nav-link" style="background:none;border:none;width:100%;text-align:left;cursor:pointer;">
                <i class="fa-solid fa-right-from-bracket"></i>
                <span>Logout</span>
            </button>
        </form>
    </div>

    <button type="button" class="sidebar-collapse-btn" id="sidebarCollapseBtn" aria-label="Collapse sidebar" title="Collapse sidebar">
        <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
    </button>
</aside>
<div class="sidebar-backdrop" id="sidebarBackdrop" aria-hidden="true"></div>

{{-- ── Topbar ─────────────────────────────────────────────────────────────── --}}
<header class="frc-topbar">
    <div class="d-flex align-items-center gap-3">
        <button type="button" class="btn-outline-teal d-md-none" id="sidebarToggle" style="padding:6px 10px;font-size:18px;" aria-label="Open menu" aria-expanded="false">
            <i class="fa-solid fa-bars" id="sidebarToggleIcon"></i>
        </button>
        <span class="page-title">@yield('page-title', 'Dashboard')</span>
    </div>
    <div class="topbar-right">
        {{-- Notifications (inbox: user_notifications) --}}
        @php
            $inboxSvc = app(\App\Services\UserNotificationService::class);
            $bellUnread = $inboxSvc->getUnreadCount((int) auth()->id());
            $bellLatest = $inboxSvc->getLatestNotifications((int) auth()->id(), 5);
        @endphp
        <div class="dropdown">
            <button type="button" class="notif-bell dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Open notifications">
                <i class="fa-regular fa-bell" aria-hidden="true"></i>
                @if($bellUnread > 0)
                    <span class="badge-dot" aria-hidden="true">{{ $bellUnread > 9 ? '9+' : $bellUnread }}</span>
                @endif
            </button>
            <div class="dropdown-menu dropdown-menu-end frc-notif-menu" role="presentation">
                <div class="frc-notif-menu-header">
                    <div class="frc-notif-menu-title" role="heading" aria-level="2">Notifications</div>
                    @if($bellUnread > 0)
                        <span class="frc-notif-menu-badge">{{ $bellUnread }} unread</span>
                    @endif
                </div>
                <div class="frc-notif-menu-scroll" role="list">
                    @forelse($bellLatest as $bn)
                        @php
                            $u = ! $bn->is_read;
                            $t = (string) ($bn->type ?? '');
                            $m = (string) ($bn->module ?? '');
                            $bellIcon = str_contains($t, 'email')
                                ? 'fa-solid fa-envelope'
                                : match ($m) {
                                    'payments' => 'fa-solid fa-receipt',
                                    'children' => 'fa-solid fa-child-reaching',
                                    'users' => 'fa-solid fa-user-check',
                                    'assessments' => 'fa-solid fa-clipboard-list',
                                    'enrollments' => 'fa-solid fa-file-contract',
                                    'sessions' => 'fa-solid fa-calendar-check',
                                    'progress_notes' => 'fa-solid fa-file-waveform',
                                    default => 'fa-solid fa-bell',
                                };
                        @endphp
                        <a href="{{ route('notifications.open', $bn) }}" class="frc-notif-item {{ $u ? 'is-unread' : '' }}" role="listitem">
                            <span class="frc-notif-item-icon" aria-hidden="true"><i class="{{ $bellIcon }}"></i></span>
                            <span class="frc-notif-item-body">
                                <span class="frc-notif-item-title">{{ \Illuminate\Support\Str::limit($bn->title, 52) }}</span>
                                <span class="frc-notif-item-msg">{{ \Illuminate\Support\Str::limit($bn->message, 90) }}</span>
                                <span class="frc-notif-item-time">{{ $bn->created_at?->diffForHumans() }}</span>
                            </span>
                        </a>
                    @empty
                        <div class="frc-notif-empty" role="status">
                            <i class="fa-regular fa-bell-slash" aria-hidden="true"></i>
                            <p><strong style="color:var(--navy);">You are all caught up.</strong><br>No new notifications right now.</p>
                        </div>
                    @endforelse
                </div>
                <div class="frc-notif-menu-footer">
                    <form action="{{ route('notifications.mark-all-read') }}" method="post" class="m-0">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-teal w-100" @if($bellUnread === 0) disabled @endif>
                            <i class="fa-regular fa-circle-check me-1"></i> Mark all as read
                        </button>
                    </form>
                    <a href="{{ route('notifications.index') }}" class="btn btn-sm btn-teal w-100 text-center text-decoration-none">
                        View all notifications <i class="fa-solid fa-arrow-right ms-1" style="font-size:10px;"></i>
                    </a>
                </div>
            </div>
        </div>

        {{-- User Menu --}}
        <div class="dropdown">
            <button class="user-avatar dropdown-toggle" data-bs-toggle="dropdown" style="border:none;">
                {{ strtoupper(substr(auth()->user()->full_name, 0, 1)) }}
            </button>
            <div class="dropdown-menu dropdown-menu-end frc-user-dropdown">
                <div class="frc-user-dropdown-head">
                    <div class="frc-user-dropdown-name">{{ auth()->user()->full_name }}</div>
                    <div class="frc-user-dropdown-meta">
                        @if(auth()->user()->isChild())
                            {{ auth()->user()->email }}
                        @else
                            {{ auth()->user()->role?->display_name }}
                        @endif
                    </div>
                </div>
                @php
                    $headerProfileHref = auth()->user()->isChild()
                        ? route('child.profile.edit')
                        : (auth()->user()->isAdmin()
                            ? route('admin.profile')
                            : (auth()->user()->isFinance()
                                ? route('finance.profile')
                                : (auth()->user()->isTherapist()
                                    ? route('therapist.profile')
                                    : null)));
                @endphp
                @if($headerProfileHref)
                    <a href="{{ $headerProfileHref }}" class="dropdown-item frc-user-dropdown-item"><i class="fa-regular fa-user me-2"></i> My profile</a>
                    <div class="dropdown-divider my-0"></div>
                @endif
                <form action="{{ route('logout') }}" method="POST" class="m-0">
                    @csrf
                    <button type="submit" class="dropdown-item frc-user-dropdown-item frc-user-dropdown-logout">
                        <i class="fa-solid fa-right-from-bracket me-2"></i> Logout
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>

{{-- ── Main Content ───────────────────────────────────────────────────────── --}}
<main class="frc-main">

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="alert-frc success mb-3">
            <i class="fa-solid fa-circle-check"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif
    @if(session('warning'))
        <div class="alert-frc warning mb-3">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <div>{{ session('warning') }}</div>
        </div>
    @endif
    @if(session('error'))
        <div class="alert-frc error mb-3">
            <i class="fa-solid fa-circle-xmark"></i>
            <div>{{ session('error') }}</div>
        </div>
    @endif
    @if($errors->any())
        <div class="alert-frc error mb-3">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <div>
                <strong>Please fix the following errors:</strong>
                <ul class="mb-0 mt-1" style="padding-left:18px;">
                    @foreach($errors->all() as $error)
                        <li style="font-size:13px;">{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    @yield('content')
</main>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
    const sidebar = document.getElementById('frcSidebar');
    const collapseBtn = document.getElementById('sidebarCollapseBtn');
    const storageKey = 'frc-sidebar-collapsed';

    document.querySelectorAll('.frc-sidebar .nav-link').forEach(function (link) {
        const label = link.querySelector('span:not(.badge-status):not(.nav-link-badge)')?.textContent?.trim();
        if (label) link.setAttribute('title', label);
    });

    function setCollapsed(collapsed) {
        document.documentElement.classList.toggle('sidebar-collapsed', collapsed);
        try {
            localStorage.setItem(storageKey, collapsed ? '1' : '0');
        } catch (e) {}
        if (collapseBtn) {
            collapseBtn.setAttribute('aria-label', collapsed ? 'Expand sidebar' : 'Collapse sidebar');
            collapseBtn.setAttribute('title', collapsed ? 'Expand sidebar' : 'Collapse sidebar');
        }
    }

    collapseBtn?.addEventListener('click', function () {
        setCollapsed(!document.documentElement.classList.contains('sidebar-collapsed'));
    });

    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarToggleIcon = document.getElementById('sidebarToggleIcon');
    const sidebarBackdrop = document.getElementById('sidebarBackdrop');
    const mobileMq = window.matchMedia('(max-width: 768px)');

    function isMobileSidebar() {
        return mobileMq.matches;
    }

    function setMobileSidebarOpen(open) {
        if (!isMobileSidebar()) {
            sidebar?.classList.remove('open');
            sidebarBackdrop?.classList.remove('show');
            document.body.classList.remove('sidebar-mobile-open');
            return;
        }
        sidebar?.classList.toggle('open', open);
        sidebarBackdrop?.classList.toggle('show', open);
        document.body.classList.toggle('sidebar-mobile-open', open);
        if (sidebarToggle) {
            sidebarToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            sidebarToggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
        }
        if (sidebarToggleIcon) {
            sidebarToggleIcon.classList.toggle('fa-bars', !open);
            sidebarToggleIcon.classList.toggle('fa-xmark', open);
        }
    }

    sidebarToggle?.addEventListener('click', function () {
        setMobileSidebarOpen(!sidebar?.classList.contains('open'));
    });

    sidebarBackdrop?.addEventListener('click', function () {
        setMobileSidebarOpen(false);
    });

    document.querySelectorAll('.frc-sidebar .nav-link').forEach(function (link) {
        link.addEventListener('click', function () {
            if (isMobileSidebar()) setMobileSidebarOpen(false);
        });
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && sidebar?.classList.contains('open')) {
            setMobileSidebarOpen(false);
        }
    });

    mobileMq.addEventListener('change', function () {
        if (!isMobileSidebar()) setMobileSidebarOpen(false);
    });
})();
</script>
@stack('scripts')
</body>
</html>
