<a href="{{ route('dashboard.child') }}" class="nav-link {{ request()->routeIs('dashboard.child') ? 'active' : '' }}">
    <i class="fa-solid fa-gauge-high"></i><span>My Dashboard</span>
</a>
<div class="nav-section-title">Updates</div>
<a href="{{ route('notifications.index') }}" class="nav-link {{ request()->routeIs('notifications.*') ? 'active' : '' }}">
    <i class="fa-solid fa-bell"></i><span>Notifications</span>
</a>
<div class="nav-section-title">My Info</div>
<a href="{{ route('child.assessments') }}" class="nav-link {{ request()->routeIs('child.assessments') ? 'active' : '' }}">
    <i class="fa-solid fa-clipboard-list"></i><span>My Assessments</span>
</a>
<a href="{{ route('child.enrollment') }}" class="nav-link {{ request()->routeIs('child.enrollment') ? 'active' : '' }}">
    <i class="fa-solid fa-file-contract"></i><span>My Enrollment</span>
</a>
<a href="{{ route('child.schedule.index') }}" class="nav-link {{ request()->routeIs('child.schedule.*') ? 'active' : '' }}">
    <i class="fa-solid fa-calendar-week"></i><span>My Schedule</span>
</a>
<a href="{{ route('child.profile.edit') }}" class="nav-link {{ request()->routeIs('child.profile.*') ? 'active' : '' }}">
    <i class="fa-regular fa-user"></i><span>My Profile</span>
</a>
@include('partials.sidebar.staff-modules')
<div class="nav-section-title">Payments</div>
<a href="{{ route('child.upload-slip') }}" class="nav-link {{ request()->routeIs('child.upload-slip') ? 'active' : '' }}">
    <i class="fa-solid fa-file-invoice"></i><span>Upload Fee Slip</span>
</a>
<a href="{{ route('child.payments') }}" class="nav-link {{ request()->routeIs('child.payments') ? 'active' : '' }}">
    <i class="fa-solid fa-receipt"></i><span>Payment History</span>
</a>
