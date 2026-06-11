<div class="nav-section-title">Updates</div>
<a href="{{ route('approval-discount.profile') }}" class="nav-link {{ request()->routeIs('approval-discount.profile') ? 'active' : '' }}">
    <i class="fa-solid fa-user"></i><span>My profile</span>
</a>
@include('partials.sidebar.staff-modules')
