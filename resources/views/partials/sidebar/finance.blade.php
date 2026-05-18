<a href="{{ route('dashboard.finance') }}" class="nav-link {{ request()->routeIs('dashboard.finance') ? 'active' : '' }}">
    <i class="fa-solid fa-gauge-high"></i><span>Dashboard</span>
</a>
<div class="nav-section-title">Updates</div>
<a href="{{ route('notifications.index') }}" class="nav-link {{ request()->routeIs('notifications.*') ? 'active' : '' }}">
    <i class="fa-solid fa-bell"></i><span>Notifications</span>
</a>
<a href="{{ route('finance.profile') }}" class="nav-link {{ request()->routeIs('finance.profile') ? 'active' : '' }}">
    <i class="fa-solid fa-user"></i><span>My profile</span>
</a>
@include('partials.sidebar.staff-modules')
