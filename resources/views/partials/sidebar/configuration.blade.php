@php
    $user = auth()->user();
    $canDisabilities = $user?->hasPermission('manage_disabilities');
    $canServices = $user?->hasPermission('manage_services');
    $canBranches = $user?->hasPermission('manage_branches');
    $showSection = $canDisabilities || $canServices || $canBranches;
@endphp

@if($showSection)
    <div class="nav-section-title">Configuration</div>
@endif
@if($canDisabilities)
    <a href="{{ route('disabilities.index') }}" class="nav-link {{ request()->routeIs('disabilities.*') ? 'active' : '' }}">
        <i class="fa-solid fa-heart-pulse"></i><span>Present Complaints</span>
    </a>
@endif
@if($canServices)
    <a href="{{ route('services.index') }}" class="nav-link {{ request()->routeIs('services.*') ? 'active' : '' }}">
        <i class="fa-solid fa-briefcase-medical"></i><span>Services</span>
    </a>
@endif
@if($canBranches)
    <a href="{{ route('branches.index') }}" class="nav-link {{ request()->routeIs('branches.*') ? 'active' : '' }}">
        <i class="fa-solid fa-building"></i><span>Branches</span>
    </a>
@endif
