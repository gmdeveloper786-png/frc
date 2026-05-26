@extends('layouts.app')
@section('title', 'Admin Dashboard')
@section('page-title', 'Admin Dashboard')

@section('content')
@php
    $adminUser = auth()->user();
    $statCards = [
        [
            'label' => 'Total Children',
            'value' => $stats['total_children'],
            'icon' => 'fa-children',
            'tone' => 'teal',
            'href' => $adminUser?->hasPermission('manage_children') ? route('children.index') : null,
        ],
        // [
        //     'label' => 'Approved Children',
        //     'value' => $stats['approved_children'],
        //     'icon' => 'fa-circle-check',
        //     'tone' => 'green',
        //     'href' => $adminUser?->hasPermission('manage_children') ? route('children.index', ['status' => 'approved']) : null,
        // ],
        [
            'label' => 'Pending Approvals',
            'value' => $stats['pending_approvals'],
            'icon' => 'fa-user-clock',
            'tone' => 'orange',
            'href' => $adminUser?->hasPermission('approve_children') ? route('children.pending') : null,
        ],
        [
                'label' => 'Pending Payment Verifications',
                'value' => $stats['pending_payment_verifications'],
                'icon' => 'fa-clock-rotate-left',
                'tone' => 'orange',
                'href' => $adminUser?->hasPermission('verify_payments') ? route('payments.pending') : null,
                ],
                [
                        'label' => 'High Discount Requests',
                        'value' => $stats['high_discount_requests'],
                        'icon' => 'fa-money-bill-trend-up',
                        'tone' => 'orange',
                        'href' => null,
                        ],
        [
            'label' => 'Total Therapists',
            'value' => $stats['total_therapists'],
            'icon' => 'fa-user-doctor',
            'tone' => 'navy',
            'href' => $adminUser?->hasPermission('manage_therapists') ? route('therapists.index') : null,
        ],
        [
            'label' => 'Total Assessments',
            'value' => number_format($stats['total_assessments']),
            'icon' => 'fa-clipboard-list',
            'tone' => 'teal',
            'href' => $adminUser?->hasPermission('manage_assessments') ? route('assessments.index', ['status' => 'completed']) :
            null,
            ],
        [
            'label' => 'Total Enrollments',
            'value' => number_format($stats['total_enrollments']),
            'icon' => 'fa-file-contract',
            'tone' => 'navy',
            'href' => $adminUser?->hasPermission('manage_enrollments') ? route('enrollments.index') : null,
        ],
        [
            'label' => 'Total Sessions Completed',
            'value' => number_format($stats['total_completed_sessions']),
            'icon' => 'fa-flag-checkered',
            'tone' => 'teal',
            'href' => null,
        ],
 


    ];
@endphp

<div class="admin-dashboard-page">
    <div class="row g-3 mb-4 admin-dashboard-stats">
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

{{-- Finance overview + quick actions --}}
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card-frc h-100">
            <div class="card-header-frc">
                <h6 class="card-title-frc">Quick Actions</h6>
            </div>
            <div style="display:flex;flex-direction:column;gap:10px;">
                @if(auth()->user()?->hasPermission('approve_children'))
                <a href="{{ route('children.pending') }}" class="btn-teal" style="justify-content:flex-start;">
                    <i class="fa-solid fa-user-check"></i> Review Child Approvals
                    @if($stats['pending_approvals'] > 0)
                        <span class="badge-status badge-pending ms-auto">{{ $stats['pending_approvals'] }}</span>
                    @endif
                </a>
                @endif
                @if(auth()->user()?->hasPermission('verify_payments'))
                <a href="{{ route('payments.pending') }}" class="btn-navy" style="justify-content:flex-start;">
                    <i class="fa-solid fa-clock-rotate-left"></i> Verify Payment Verifications
                    @if($stats['pending_payment_verifications'] > 0)
                        <span class="badge-status badge-pending ms-auto">{{ $stats['pending_payment_verifications'] }}</span>
                    @endif
                </a>
                @endif
                @if(auth()->user()?->hasPermission('manage_payments'))
                <a href="{{ route('payments.manual.create') }}" class="btn-teal" style="justify-content:flex-start;">
                    <i class="fa-solid fa-hand-holding-dollar"></i> Add Manual Payment
                </a>
                @endif
                @if(auth()->user()?->hasPermission('manage_assessments'))
                <a href="{{ route('assessments.create') }}" class="btn-navy" style="justify-content:flex-start;">
                    <i class="fa-solid fa-clipboard-list"></i> Schedule New Assessment
                </a>
                @endif
                @if(auth()->user()?->hasPermission('manage_enrollments'))
                <a href="{{ route('enrollments.create') }}" class="btn-teal" style="justify-content:flex-start;">
                    <i class="fa-solid fa-file-contract"></i> Create New Enrollment
                </a>
                @endif
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
