<a href="{{ route('dashboard.super-admin') }}" class="nav-link {{ request()->routeIs('dashboard.super-admin') ? 'active' : '' }}">
    <i class="fa-solid fa-gauge-high"></i><span>Dashboard</span>
</a>
<div class="nav-section-title">Updates</div>
<a href="{{ route('notifications.index') }}" class="nav-link {{ request()->routeIs('notifications.*') ? 'active' : '' }}">
    <i class="fa-solid fa-bell"></i><span>Notifications</span>
</a>
<div class="nav-section-title">Users</div>
<a href="{{ route('super-admin.staff-users.index') }}" class="nav-link {{ request()->routeIs('super-admin.staff-users.*') ? 'active' : '' }}">
    <i class="fa-solid fa-user-tie"></i><span>Staff Users</span>
</a>
<a href="{{ route('super-admin.roles.index') }}" class="nav-link {{ request()->routeIs('super-admin.roles.*') ? 'active' : '' }}">
    <i class="fa-solid fa-shield-halved"></i><span>Roles &amp; Permissions</span>
</a>
@include('partials.sidebar.staff-modules')
