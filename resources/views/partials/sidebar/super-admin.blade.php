<a href="{{ route('dashboard.super-admin') }}" class="nav-link {{ request()->routeIs('dashboard.super-admin') ? 'active' : '' }}">
    <i class="fa-solid fa-gauge-high"></i><span>Dashboard</span>
</a>
<div class="nav-section-title">Updates</div>
<a href="{{ route('notifications.index') }}" class="nav-link {{ request()->routeIs('notifications.*') ? 'active' : '' }}">
    <i class="fa-solid fa-bell"></i><span>Notifications</span>
</a>
@include('partials.sidebar.staff-modules')
