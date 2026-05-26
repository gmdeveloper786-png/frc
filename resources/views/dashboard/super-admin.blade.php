@extends('layouts.app')
@section('title', 'Super Admin Dashboard')
@section('page-title', 'Super Admin Dashboard')

@section('content')
@php
    $statCards = [
        ['label' => 'Total Children', 'value' => number_format($stats['total_children']), 'icon' => 'fa-children', 'tone' => 'teal', 'href' => route('children.index')],
        // ['label' => 'Approved Children', 'value' => number_format($stats['approved_children']), 'icon' => 'fa-circle-check', 'tone' => 'green', 'href' => route('children.index', ['status' => 'approved'])],
        ['label' => 'Pending Approvals', 'value' => number_format($stats['pending_approvals']), 'icon' => 'fa-user-clock', 'tone' => 'orange', 'href' => route('children.pending')],
        ['label' => 'Pending Payment Verifications', 'value' => number_format($stats['pending_payment_verifications']), 'icon'
                => 'fa-clock-rotate-left', 'tone' => 'orange', 'href' => route('payments.pending')],
        ['label' => 'High Discount Requests', 'value' => number_format($stats['pending_high_discount']), 'icon' => 'fa-money-bill-trend-up',
                'tone' => 'orange', 'href' => route('enrollments.high-discount')],
        ['label' => 'Total Therapists', 'value' => number_format($stats['total_therapists']), 'icon' => 'fa-user-doctor', 'tone' => 'navy', 'href' => route('therapists.index')],
        ['label' => 'Total Assessments', 'value' => number_format($stats['total_assessments']), 'icon' => 'fa-clipboard-list', 'tone' => 'teal', 'href' => route('assessments.index', ['status' => 'completed'])],
        ['label' => 'Total Enrollments', 'value' => number_format($stats['total_enrollments']), 'icon' => 'fa-file-contract', 'tone' => 'navy', 'href' => route('enrollments.index')],
        ['label' => 'Total Sessions Completed', 'value' => number_format($stats['total_completed_sessions']), 'icon' => 'fa-flag-checkered', 'tone' => 'teal', 'href' => null],

    ];
@endphp

<div class="super-admin-dashboard-page">
    <div class="row g-3 mb-4 super-admin-dashboard-stats">
        @foreach($statCards as $card)
            <div class="col-md-3 col-sm-6">
                @if(! empty($card['href']))
                    <a href="{{ $card['href'] }}" class="stat-card-link d-block h-100 text-reset text-decoration-none rounded-3">
                @endif
                <div class="stat-card h-100 {{ ! empty($card['href']) ? 'stat-card--clickable' : '' }}">
                    <div class="stat-icon {{ $card['tone'] }}"><i class="fa-solid {{ $card['icon'] }}"></i></div>
                    <div class="stat-body">
                        <div class="stat-value">{{ $card['value'] }}</div>
                        <div class="stat-label">{{ $card['label'] }}</div>
                    </div>
                </div>
                @if(! empty($card['href']))
                    </a>
                @endif
            </div>
        @endforeach
    </div>

@include('dashboard.partials.fee-summary-cards', ['stats' => $stats])

{{-- Recent Children, Enrollments, and Payments --}}
<div class="row g-3 mb-4 dashboard-recent-row">
    <div class="col-12 col-md-4">
        <div class="card-frc card-frc--panel h-100">
            <div class="card-header-frc">
                <h6 class="card-title-frc"><i class="fa-solid fa-child me-2" style="color:var(--teal);"></i>Recent Children</h6>
                <a href="{{ route('children.index') }}" class="btn-outline-teal btn-view-all">View all</a>
            </div>
            @if($stats['recent_children']->isEmpty())
            <p class="text-muted small card-empty-msg mb-0">No children yet.</p>
            @else
            <ul class="list-unstyled mb-0 p-3 pt-0">
                @foreach($stats['recent_children'] as $child)
                <li class="py-2 border-bottom" style="border-color:var(--border-soft)!important;">
                    <a href="{{ route('children.show', $child) }}" style="font-weight:600;color:var(--navy);">{{
                        $child->full_name }}</a>
                    <div class="small text-muted">{{ ucfirst($child->status) }} · {{ $child->created_at->diffForHumans()
                        }}</div>
                </li>
                @endforeach
            </ul>
            @endif
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card-frc card-frc--panel h-100">
            <div class="card-header-frc">
                <h6 class="card-title-frc"><i class="fa-solid fa-file-contract me-2" style="color:var(--teal);"></i>Recent Enrollments</h6>
                <a href="{{ route('enrollments.index') }}" class="btn-outline-teal btn-view-all">View all</a>
            </div>
            @if($stats['recent_enrollments']->isEmpty())
            <p class="text-muted small card-empty-msg mb-0">No enrollments yet.</p>
            @else
            <ul class="list-unstyled mb-0 p-3 pt-0">
                @foreach($stats['recent_enrollments'] as $enr)
                <li class="py-2 border-bottom" style="border-color:var(--border-soft)!important;">
                    <a href="{{ route('enrollments.show', $enr) }}" style="font-weight:600;color:var(--navy);">{{
                        $enr->child?->full_name ?? '—' }}</a>
                    <div class="small text-muted">{{ $enr->service?->name ?? 'Programme' }} · {{
                        $enr->created_at->diffForHumans() }}</div>
                </li>
                @endforeach
            </ul>
            @endif
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card-frc card-frc--panel h-100">
            <div class="card-header-frc">
                <h6 class="card-title-frc"><i class="fa-solid fa-money-bill-wave me-2" style="color:var(--teal);"></i>Recent Payments</h6>
                <a href="{{ route('payments.index') }}" class="btn-outline-teal btn-view-all">View all</a>
            </div>
            @if($stats['recent_payments']->isEmpty())
            <p class="text-muted small card-empty-msg mb-0">No payments yet.</p>
            @else
            <ul class="list-unstyled mb-0 p-3 pt-0">
                @foreach($stats['recent_payments'] as $pay)
                <li class="py-2 border-bottom" style="border-color:var(--border-soft)!important;">
                    <div style="font-weight:600;color:var(--navy);">PKR {{ frc_money($pay->amount) }}</div>
                    <div class="small text-muted">{{ $pay->child?->full_name ?? '—' }} · {{ ucfirst(str_replace('_', '
                        ', $pay->status)) }} · {{ $pay->created_at->diffForHumans() }}</div>
                </li>
                @endforeach
            </ul>
            @endif
        </div>
    </div>
</div>

{{-- Quick Actions + Charts --}}
<div class="row g-3">
    {{-- Quick Actions --}}
    <div class="col-md-4">
        <div class="card-frc h-100">
            <div class="card-header-frc">
                <h6 class="card-title-frc">Quick Actions</h6>
            </div>
            <div style="display:flex;flex-direction:column;gap:10px;">
                <a href="{{ route('children.pending') }}" class="btn-teal" style="justify-content:flex-start;">
                    <i class="fa-solid fa-user-check"></i> Review Child Approvals
                    @if($stats['pending_approvals'] > 0)
                        <span class="badge-status badge-pending ms-auto">{{ $stats['pending_approvals'] }}</span>
                    @endif
                </a>
                <a href="{{ route('enrollments.high-discount') }}" class="btn-navy" style="justify-content:flex-start;">
                    <i class="fa-solid fa-percent"></i> High Discount Approvals
                    @if($stats['pending_high_discount'] > 0)
                        <span class="badge-status badge-pending ms-auto">{{ $stats['pending_high_discount'] }}</span>
                    @endif
                </a>
                <a href="{{ route('payments.pending') }}" class="btn-teal" style="justify-content:flex-start;">
                    <i class="fa-solid fa-clock-rotate-left"></i> Verify Payments
                    @if($stats['pending_payment_verifications'] > 0)
                        <span class="badge-status badge-pending ms-auto">{{ $stats['pending_payment_verifications'] }}</span>
                    @endif
                </a>
                <a href="{{ route('payments.manual.create') }}" class="btn-navy" style="justify-content:flex-start;">
                    <i class="fa-solid fa-hand-holding-dollar"></i> Add Manual Payment
                </a>
                <a href="{{ route('assessments.create') }}" class="btn-teal" style="justify-content:flex-start;">
                    <i class="fa-solid fa-clipboard-list"></i> Schedule New Assessment
                </a>
                <a href="{{ route('enrollments.create') }}" class="btn-navy" style="justify-content:flex-start;">
                    <i class="fa-solid fa-file-contract"></i> Create New Enrollment
                </a>

                <a href="{{ route('therapists.create') }}" class="btn-teal" style="justify-content:flex-start;">
                    <i class="fa-solid fa-user-plus"></i> Add New Therapist
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        @include('dashboard.partials.finance-overview', ['stats' => $stats, 'showYearFilter' => true])
    </div>
</div>

@include('dashboard.partials.analytics-grid', ['stats' => $stats])

</div>

@endsection

@push('scripts')
    @include('dashboard.partials.chart-scripts', ['stats' => $stats])
@endpush
