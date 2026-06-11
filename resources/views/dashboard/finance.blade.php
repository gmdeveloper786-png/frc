@extends('layouts.app')
@section('title', 'Finance Dashboard')
@section('page-title', 'Finance Dashboard')

@section('content')
@php
    $user = auth()->user();
    $isFinance = $user?->isFinance();
    $paymentsPendingRoute = $isFinance ? 'finance.payments.pending' : 'payments.pending';
    $paymentsManualRoute = $isFinance ? 'finance.payments.manual.create' : 'payments.manual.create';
    $paymentsIndexRoute = $isFinance ? 'finance.payments' : 'payments.index';
    $paymentsReceiptRoute = $isFinance ? 'finance.payments.receipt' : 'payments.receipt';
    $reportsFinanceRoute = $isFinance ? 'finance.reports' : 'reports.finance';
    $showFinanceStats = $user?->hasPermission('view_finance_reports')
        || $user?->hasPermission('manage_payments')
        || $user?->hasPermission('verify_payments');
@endphp

<div class="finance-dashboard-page">
@if($showFinanceStats)
<div class="row g-3 mb-4 finance-dashboard-stats">
    <div class="col-12 col-sm-6 col-md-4">
        <div class="stat-card">
            <div class="stat-icon navy"><i class="fa-solid fa-money-bill-trend-up"></i></div>
            <div class="stat-body">
                <div class="stat-value" style="font-size:16px; color:var(--navy);">{{ frc_pkr($stats['total_expected']) }}</div>
                <div class="stat-label">Total Expected Fees</div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-4">
        <div class="stat-card">
            <div class="stat-icon green"><i class="fa-solid fa-circle-check"></i></div>
            <div class="stat-body">
                <div class="stat-value" style="font-size:16px; color:var(--success);">{{ frc_pkr($stats['total_paid']) }}</div>
                <div class="stat-label">Total Collected</div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-4">
        <div class="stat-card">
            <div class="stat-icon red"><i class="fa-solid fa-hourglass-half"></i></div>
            <div class="stat-body">
                <div class="stat-value" style="font-size:16px; color:var(--danger);">{{ frc_pkr($stats['total_pending']) }}</div>
                <div class="stat-label">Pending</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4 finance-dashboard-fee-stats">
    <div class="col-12 col-sm-6 col-md-4 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon teal"><i class="fa-solid fa-money-bills"></i></div>
            <div class="stat-body">
                <div class="stat-value" style="font-size:16px; color:var(--teal-dark);">{{ frc_pkr($stats['cash_received']) }}</div>
                <div class="stat-label">Cash Received</div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-4 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon purple"><i class="fa-solid fa-mobile-screen"></i></div>
            <div class="stat-body">
                <div class="stat-value" style="font-size:16px; color:#7c3aed;;">{{ frc_pkr($stats['online_received']) }}</div>
                <div class="stat-label">Online/Bank</div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-4 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon orange"><i class="fa-solid fa-money-bill-trend-up"></i></div>
                <div class="stat-body">
                    <div class="stat-value" style="font-size:16px; color:#e08000;">{{ frc_pkr($stats['pending_verification_amount']) }}</div>
                    <div class="stat-label">Pending Verification Amount</div>
                </div>
            </div>
        </div>
    <div class="col-12 col-sm-6 col-md-4 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon orange"><i class="fa-solid fa-clock-rotate-left"></i></div>
            <div class="stat-body">
                <div class="stat-value" style="font-size:16px; color:#e08000;">{{ $stats['pending_verifications'] }}</div>
                <div class="stat-label">Pending Payment Verifications</div>
            </div>
        </div>
    </div>

</div>
@endif

<div class="row g-3 mb-4">
    <div class="col-12 col-lg-4">
        <div class="card-frc h-100">
            <div class="card-header-frc">
                <h6 class="card-title-frc">Quick Actions</h6>
            </div>
            <div class="finance-quick-actions">

                <a href="{{ route('payments.index') }}" class="btn-teal" style="justify-content:flex-start;">
                    <i class="fa-solid fa-money-bills"></i> View All Payments
                </a>
                @if($user?->hasPermission('approve_children'))
                <a href="{{ route('children.pending') }}" class="btn-outline-teal" style="justify-content:flex-start;">
                    <i class="fa-solid fa-user-check"></i> Review Approvals
                </a>
                @endif
                @if($user?->hasPermission('verify_payments'))
                <a href="{{ route($paymentsPendingRoute) }}" class="btn-teal" style="justify-content:flex-start;">
                    <i class="fa-solid fa-clock-rotate-left"></i> Verify Payments
                    @if($stats['pending_verifications'] > 0)
                    <span class="badge-status badge-pending ms-auto">{{ $stats['pending_verifications'] }}</span>
                    @endif
                </a>
                @endif
                @if($user?->hasPermission('manage_payments'))
                <a href="{{ route($paymentsManualRoute) }}" class="btn-navy" style="justify-content:flex-start;">
                    <i class="fa-solid fa-hand-holding-dollar"></i> Add Manual Payment
                </a>
                @endif
                @if($user?->hasPermission('view_finance_reports'))
                <a href="{{ route($reportsFinanceRoute) }}" class="btn-teal"
                    style="justify-content:flex-start;">
                    <i class="fa-solid fa-chart-line"></i> Finance Reports
                </a>
                @endif
                <a href="{{ route('notifications.index') }}" class="btn-teal"
                    style="justify-content:flex-start;">
                    <i class="fa-solid fa-bell"></i> Notifications
                </a>
            </div>
        </div>
    </div>

    @if($user?->hasPermission('manage_payments') || $user?->hasPermission('verify_payments'))
    <div class="col-12 col-lg-8">
        <div class="card-frc h-100">
            <div class="card-header-frc card-header-frc--stack-sm">
                <h6 class="card-title-frc mb-0"><i class="fa-solid fa-receipt me-2" style="color:var(--teal);"></i>Recent Payments</h6>
                <a href="{{ route($paymentsIndexRoute) }}" class="btn-outline-teal btn-view-all">View All</a>
            </div>
            @if($stats['recent_payments']->isEmpty())
            <div class="empty-state" style="padding:30px;">
                <i class="fa-solid fa-receipt empty-icon" style="font-size:40px;"></i>
                <p>No payments recorded yet</p>
            </div>
            @else
            <div class="frc-table-wrap table-scroll finance-recent-payments-table">
                <table class="table-frc mb-0">
                    <thead>
                        <tr>
                            <th>Child</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($stats['recent_payments'] as $payment)
                        <tr>
                            <td style="font-weight:500; white-space:nowrap;">{{ $payment->child->full_name ?? 'N/A' }}</td>
                            <td style="color:var(--teal);font-weight:600;">{{ frc_pkr($payment->amount) }}
                            </td>
                            <td style="white-space:nowrap;"><span style="font-size:13px;">{{ ucfirst(str_replace('_',' ',$payment->payment_method))
                                    }}</span></td>
                            <td style="color:var(--text-muted);font-size:13px; white-space:nowrap;">{{ $payment->payment_date?->format('d M
                                Y') }}</td>
                            <td style="white-space:nowrap;"><span class="badge-status badge-{{ $payment->status }}">{{ ucfirst(str_replace('_','
                                    ',$payment->status)) }}</span></td>
                            <td>
                                @if($payment->hasPrintableReceipt())
                                <a href="{{ route($paymentsReceiptRoute, $payment->id) }}" class="btn-outline-teal"
                                    style="font-size:12px;padding:4px 10px;">Receipt</a>
                                @elseif($payment->status === 'pending_verification' &&
                                $user?->hasPermission('verify_payments'))
                                <a href="{{ route($paymentsPendingRoute) }}" class="btn-teal"
                                    style="font-size:12px;padding:4px 10px;">Verify</a>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>
    @endif
</div>

@if($showFinanceStats)
<div class="row g-3 mb-4">
    <div class="col-12">
        @include('dashboard.partials.finance-overview', ['stats' => $stats, 'showYearFilter' => true])
    </div>
</div>
@include('dashboard.partials.analytics-grid', ['stats' => $stats])
@endif
</div>

@endsection

@push('scripts')
@if($showFinanceStats)
    @include('dashboard.partials.chart-scripts', ['stats' => $stats])
@endif
@endpush
