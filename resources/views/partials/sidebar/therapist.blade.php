<a href="{{ route('dashboard.therapist') }}" class="nav-link {{ request()->routeIs('dashboard.therapist') ? 'active' : '' }}">
    <i class="fa-solid fa-gauge-high"></i><span>Dashboard</span>
</a>
<div class="nav-section-title">My Work</div>
<a href="{{ route('therapist.assessments.index') }}" class="nav-link {{ request()->routeIs('therapist.assessments.*') ? 'active' : '' }}">
    <i class="fa-solid fa-clipboard-list"></i><span>My Assessments</span>
</a>
<a href="{{ route('therapist.sessions.index') }}" class="nav-link {{ request()->routeIs('therapist.sessions.*') ? 'active' : '' }}">
    <i class="fa-solid fa-calendar-days"></i><span>My Session Scheduled</span>
</a>
@if(auth()->user()?->hasPermission('view_assigned_children'))
<a href="{{ route('therapist.children.index') }}" class="nav-link {{ request()->routeIs('therapist.children.*') ? 'active' : '' }}">
    <i class="fa-solid fa-children"></i><span>Assigned Children</span>
</a>
@endif
@include('partials.sidebar.staff-modules')
<div class="nav-section-title">Updates</div>
<a href="{{ route('notifications.index') }}" class="nav-link {{ request()->routeIs('notifications.*') ? 'active' : '' }}">
    <i class="fa-solid fa-bell"></i><span>Notifications</span>
</a>
<div class="nav-section-title">Account</div>
<a href="{{ route('therapist.profile') }}" class="nav-link {{ request()->routeIs('therapist.profile') || request()->routeIs('therapist.profile.password') ? 'active' : '' }}">
    <i class="fa-solid fa-user"></i><span>My Profile</span>
</a>
