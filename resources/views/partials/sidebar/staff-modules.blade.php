@php
    $user = auth()->user();
    if (! $user) {
        return;
    }

    $canViewStaffUsers = $user->hasPermission('view_staff_users');
    $canApproveChildren = $user->hasPermission('approve_children');
    $canManageChildren = $user->hasPermission('manage_children');
    $canRegisterChildren = $user->hasPermission('register_children');
    $canViewChildren = $user->hasPermission('view_children');
    $canTherapists = $user->hasPermission('manage_therapists');
    $canDisabilities = $user->hasPermission('manage_disabilities');
    $canServices = $user->hasPermission('manage_services');
    $canBranches = $user->hasPermission('manage_branches');
    $canAssessments = $user->hasPermission('manage_assessments');
    $canEnrollments = $user->hasPermission('manage_enrollments');
    $canViewEnrollments = $user->hasPermission('view_enrollments');
    $canHighDiscount = $user->hasPermission('approve_high_discount');
    $canManagePayments = $user->hasPermission('manage_payments');
    $canVerifyPayments = $user->hasPermission('verify_payments');
    $canFinanceReports = $user->hasPermission('view_finance_reports');
    $canSettings = $user->hasPermission('manage_settings');

    $showUsers = $canViewStaffUsers || $user->isSuperAdmin();
    $showChildren = $canApproveChildren || $canManageChildren || $canRegisterChildren || $canViewChildren;
    $showConfiguration = $canTherapists || $canDisabilities || $canServices || $canBranches;
    $showClinical = $canAssessments || $canEnrollments || $canViewEnrollments || $canHighDiscount;
    $showFinance = $canManagePayments || $canVerifyPayments || $canFinanceReports;
@endphp

@if($showUsers)
    <div class="nav-section-title">Users</div>
    @if($canViewStaffUsers)
        <a href="{{ route('super-admin.staff-users.index') }}" class="nav-link {{ request()->routeIs('super-admin.staff-users.*') ? 'active' : '' }}">
            <i class="fa-solid fa-user-tie"></i><span>Staff Users</span>
        </a>
    @endif
    @if($user->isSuperAdmin())
        <a href="{{ route('super-admin.roles.index') }}" class="nav-link {{ request()->routeIs('super-admin.roles.*') ? 'active' : '' }}">
            <i class="fa-solid fa-shield-halved"></i><span>Roles &amp; Permissions</span>
        </a>
    @endif
@endif

@if($showChildren)
    <div class="nav-section-title">Children</div>
    @if($canApproveChildren)
        <a href="{{ route('children.pending') }}" class="nav-link {{ request()->routeIs('children.pending') ? 'active' : '' }}">
            <i class="fa-solid fa-user-clock"></i><span>Pending Approvals</span>
            @php
                $pendingCountQuery = \App\Models\User::children()->pending();
                if ($user->isAdmin() && ! $user->isSuperAdmin()) {
                    $pendingCountQuery = $pendingCountQuery->visibleToStaff($user);
                }
                $pendingCount = $pendingCountQuery->count();
            @endphp
            @if($pendingCount > 0)
                <span class="nav-link-badge badge-status badge-pending" data-short="{{ $pendingCount > 99 ? '99+' : $pendingCount }}">{{ $pendingCount }}</span>
            @endif
        </a>
    @endif
    @if($canManageChildren || $canViewChildren)
        <a href="{{ route('children.index') }}" class="nav-link {{ request()->routeIs('children.*') && ! request()->routeIs('children.pending', 'children.create') ? 'active' : '' }}">
            <i class="fa-solid fa-children"></i><span>All Children</span>
        </a>
    @endif
    @if($canRegisterChildren)
        <a href="{{ route('children.create') }}" class="nav-link {{ request()->routeIs('children.create') ? 'active' : '' }}">
            <i class="fa-solid fa-user-plus"></i><span>Register Child</span>
        </a>
    @endif
@endif

@if($showConfiguration)
    <div class="nav-section-title">Configuration</div>
    @if($canTherapists)
        <a href="{{ route('therapists.index') }}" class="nav-link {{ request()->routeIs('therapists.*') ? 'active' : '' }}">
            <i class="fa-solid fa-user-doctor"></i><span>Therapists</span>
        </a>
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
@endif

@if($showClinical)
    <div class="nav-section-title">Clinical</div>
    @if($canAssessments)
        <a href="{{ route('assessments.index') }}" class="nav-link {{ request()->routeIs('assessments.*') ? 'active' : '' }}">
            <i class="fa-solid fa-clipboard-list"></i><span>Assessments</span>
        </a>
    @endif
    @if($canEnrollments || $canViewEnrollments)
        <a href="{{ route('enrollments.index') }}" class="nav-link {{ request()->routeIs('enrollments.index', 'enrollments.create', 'enrollments.show', 'enrollments.edit', 'enrollments.schedule*') ? 'active' : '' }}">
            <i class="fa-solid fa-file-contract"></i><span>Enrollments</span>
        </a>
    @endif
    @if($canHighDiscount)
        <a href="{{ route('enrollments.high-discount') }}" class="nav-link {{ request()->routeIs('enrollments.high-discount') ? 'active' : '' }}">
            <i class="fa-solid fa-percent"></i><span>High Discount</span>
            @php $hdCount = \App\Models\Enrollment::where('status', 'pending_super_admin_approval')->count(); @endphp
            @if($hdCount > 0)
                <span class="nav-link-badge badge-status badge-pending" data-short="{{ $hdCount > 99 ? '99+' : $hdCount }}">{{ $hdCount }}</span>
            @endif
        </a>
    @endif
@endif

@if($canSettings)
    <div class="nav-section-title">System</div>
    <a href="{{ route('settings.edit') }}" class="nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }}">
        <i class="fa-solid fa-gear"></i><span>Settings</span>
    </a>
@endif

@if($showFinance)
    <div class="nav-section-title">Finance</div>
    @if($canManagePayments)
        <a href="{{ route('payments.index') }}" class="nav-link {{ request()->routeIs('payments.index') ? 'active' : '' }}">
            <i class="fa-solid fa-money-bills"></i><span>Payments</span>
        </a>
        <a href="{{ route('payments.manual.create') }}" class="nav-link {{ request()->routeIs('payments.manual.*') ? 'active' : '' }}">
            <i class="fa-solid fa-hand-holding-dollar"></i><span>Manual Payment</span>
        </a>
    @endif
    @if($canVerifyPayments)
        <a href="{{ route('payments.pending') }}" class="nav-link {{ request()->routeIs('payments.pending') ? 'active' : '' }}">
            <i class="fa-solid fa-clock-rotate-left"></i><span>Verify Payments</span>
            @php $pvCount = \App\Support\StaffBranchScope::pendingPaymentVerificationCount($user); @endphp
            @if($pvCount > 0)
                <span class="nav-link-badge badge-status badge-pending" data-short="{{ $pvCount > 99 ? '99+' : $pvCount }}">{{ $pvCount }}</span>
            @endif
        </a>
    @endif
    @if($canFinanceReports)
        <a href="{{ route('reports.finance') }}" class="nav-link {{ request()->routeIs('reports.finance*') ? 'active' : '' }}">
            <i class="fa-solid fa-chart-line"></i><span>Finance Reports</span>
        </a>
    @endif
@endif
