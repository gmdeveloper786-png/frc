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

@if($showFinanceStats)
<div class="row g-3 mb-4">
    <div class="col-md-4 col-sm-6">
        <div class="stat-card">
            <div class="stat-icon navy"><i class="fa-solid fa-money-bill-trend-up"></i></div>
            <div class="stat-body">
                <div class="stat-value" style="font-size:20px;">PKR {{ number_format($stats['total_expected']) }}</div>
                <div class="stat-label">Total Expected Fees</div>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-sm-6">
        <div class="stat-card">
            <div class="stat-icon green"><i class="fa-solid fa-circle-check"></i></div>
            <div class="stat-body">
                <div class="stat-value" style="font-size:20px;">PKR {{ number_format($stats['total_paid']) }}</div>
                <div class="stat-label">Total Collected</div>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-sm-6">
        <div class="stat-card">
            <div class="stat-icon red"><i class="fa-solid fa-circle-exclamation"></i></div>
            <div class="stat-body">
                <div class="stat-value" style="font-size:20px;">PKR {{ number_format($stats['total_pending']) }}</div>
                <div class="stat-label">Total Pending</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3 col-sm-6">
        <div class="stat-card">
            <div class="stat-icon teal"><i class="fa-solid fa-money-bills"></i></div>
            <div class="stat-body">
                <div class="stat-value" style="font-size:18px;">PKR {{ number_format($stats['cash_received']) }}</div>
                <div class="stat-label">Cash Received</div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="stat-card">
            <div class="stat-icon navy"><i class="fa-solid fa-mobile-screen"></i></div>
            <div class="stat-body">
                <div class="stat-value" style="font-size:18px;">PKR {{ number_format($stats['online_received']) }}</div>
                <div class="stat-label">Online Received</div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="stat-card">
            <div class="stat-icon orange"><i class="fa-solid fa-clock-rotate-left"></i></div>
            <div class="stat-body">
                <div class="stat-value">{{ $stats['pending_verifications'] }}</div>
                <div class="stat-label">Pending Verification</div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="stat-card">
            <div class="stat-icon orange"><i class="fa-solid fa-triangle-exclamation"></i></div>
            <div class="stat-body">
                <div class="stat-value" style="font-size:18px;">PKR {{ number_format($stats['pending_verification_amount']) }}</div>
                <div class="stat-label">Verification Amount</div>
            </div>
        </div>
    </div>
</div>
@endif

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card-frc">
            <div class="card-header-frc">
                <h6 class="card-title-frc">Quick Actions</h6>
            </div>
            <div style="display:flex;flex-direction:column;gap:10px;">

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
    <div class="col-md-9">
        <div class="card-frc">
            <div class="card-header-frc">
                <h6 class="card-title-frc"><i class="fa-solid fa-receipt me-2" style="color:var(--teal);"></i>Recent
                    Payments</h6>
                <a href="{{ route($paymentsIndexRoute) }}" class="btn-outline-teal"
                    style="font-size:13px;padding:5px 14px;">View All</a>
            </div>
            @if($stats['recent_payments']->isEmpty())
            <div class="empty-state" style="padding:30px;">
                <i class="fa-solid fa-receipt empty-icon" style="font-size:40px;"></i>
                <p>No payments recorded yet</p>
            </div>
            @else
            <div class="table-responsive">
                <table class="table-frc">
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
                            <td style="color:var(--teal);font-weight:600;">PKR {{ number_format($payment->amount) }}
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


@endsection

@push('scripts')
@if($showFinanceStats)
    @include('dashboard.partials.chart-scripts', ['stats' => $stats])
@endif
@endpush
