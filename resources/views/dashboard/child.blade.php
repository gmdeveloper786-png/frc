@extends('layouts.app')
@section('title', 'My Dashboard')
@section('page-title', 'My Dashboard')

@section('content')
@php
    $summary = $stats['dashboard_summary'] ?? [];
    $totalEnrollments = (int) ($summary['total_enrollments'] ?? 0);
    $totalAssessments = (int) ($summary['total_assessments'] ?? 0);
    $slipsPending = (int) ($summary['slips_pending'] ?? 0);
    $totalExpected = (float) ($summary['total_expected'] ?? 0);
    $totalPaid = (float) ($summary['total_paid'] ?? 0);
    $pendingOverdue = (float) ($summary['pending_overdue'] ?? 0);
    $canUploadSlip = (bool) ($stats['can_upload_fee_slip'] ?? false);

    $formatPkr = fn (float $amount): string => 'PKR ' . number_format($amount, 2);

    $statCards = [
        [
            'label' => 'Total enrollment',
            'value' => $totalEnrollments,
            'icon' => 'fa-file-contract',
            'tone' => 'navy',
            'href' => route('child.enrollment'),
        ],
        [
            'label' => 'Total Assessment',
            'value' => $totalAssessments,
            'icon' => 'fa-clipboard-list',
            'tone' => 'teal',
            'href' => route('child.assessments'),
        ],
        [
            'label' => 'Slip Pending',
            'value' => $slipsPending,
            'icon' => 'fa-file-circle-exclamation',
            'tone' => $slipsPending > 0 ? 'orange' : 'teal',
            'href' => route('child.payments'),
            'hint' => $slipsPending > 0 ? 'Awaiting verification' : null,
        ],
        [
            'label' => 'Total Expected',
            'value' => $totalEnrollments > 0 ? $formatPkr($totalExpected) : '—',
            'icon' => 'fa-receipt',
            'tone' => 'navy',
            'href' => route('child.enrollment'),
        ],
        [
            'label' => 'Total Paid',
            'value' => $totalEnrollments > 0 ? $formatPkr($totalPaid) : '—',
            'icon' => 'fa-circle-check',
            'tone' => 'green',
            'href' => route('child.payments'),
        ],
        [
            'label' => 'Pending / Overdue',
            'value' => $totalEnrollments > 0 ? $formatPkr($pendingOverdue) : '—',
            'icon' => 'fa-wallet',
            'tone' => $pendingOverdue > 0 ? 'orange' : 'green',
            'href' => $canUploadSlip ? route('child.upload-slip') : route('child.enrollment'),
            'hint' => $pendingOverdue > 0 && $canUploadSlip ? 'Upload fee slip' : null,
        ],
    ];
@endphp

<div class="child-dashboard-page">
    <div class="row g-3 mb-4 child-dashboard-stats">
        @foreach($statCards as $card)
            <div class="col-12 col-sm-6 col-xl-4">
                @if(! empty($card['href']))
                    <a href="{{ $card['href'] }}" class="stat-card-link d-block h-100 text-reset text-decoration-none rounded-3">
                @endif
                <div class="stat-card h-100 {{ ! empty($card['href']) ? 'stat-card--clickable' : '' }}">
                    <div class="stat-icon {{ $card['tone'] }}"><i class="fa-solid {{ $card['icon'] }}"></i></div>
                    <div class="stat-body">
                        <div class="stat-value child-dashboard-stat-value">{{ $card['value'] }}</div>
                        <div class="stat-label">{{ $card['label'] }}</div>
                        @if(! empty($card['hint']))
                            <div class="stat-hint">{{ $card['hint'] }}</div>
                        @endif
                    </div>
                </div>
                @if(! empty($card['href']))
                    </a>
                @endif
            </div>
        @endforeach
    </div>

    <div class="row g-3 child-dashboard-recent-row">
        <div class="col-lg-6">
            <div class="card-frc h-100 child-dashboard-panel">
                <div class="card-header-frc d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h6 class="card-title-frc mb-0"><i class="fa-solid fa-clipboard-list me-2" style="color:var(--teal);"></i>Recent assessments</h6>
                    <a href="{{ route('child.assessments') }}" class="btn-outline-teal child-dashboard-panel-btn">View all</a>
                </div>
                @if($stats['assessments']->isEmpty())
                    <div class="empty-state py-4">
                        <p class="text-muted mb-0 small">No assessments yet.</p>
                    </div>
                @else
                    <div class="table-responsive child-dashboard-table-wrap">
                        <table class="table-frc mb-0">
                            <thead><tr><th>Date</th><th>Time</th><th>Status</th><th> Actions</th></tr></thead>
                            <tbody>
                                @foreach($stats['assessments']->take(5) as $a)
                                    <tr>
                                        <td>{{ $a->date->format('d M Y') }}</td>
                                        <td>{{ \Carbon\Carbon::parse($a->time)->format('h:i A') }}</td>
                                        <td><span class="badge-status badge-{{ $a->status }}">{{ ucfirst($a->status) }}</span></td>
                                        <td><a href="{{ route('child.assessments.show', $a) }}" class="btn-outline-teal btn-sm-frc">View Details</a></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card-frc h-100 child-dashboard-panel">
                <div class="card-header-frc d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h6 class="card-title-frc mb-0"><i class="fa-solid fa-receipt me-2" style="color:var(--teal);"></i>Recent payments</h6>
                    <a href="{{ route('child.payments') }}" class="btn-outline-teal child-dashboard-panel-btn">View all</a>
                </div>
                @if($stats['payments']->isEmpty())
                    <div class="empty-state py-4">
                        <p class="text-muted mb-0 small">No payments yet.</p>
                    </div>
                @else
                    <div class="table-responsive child-dashboard-table-wrap">
                        <table class="table-frc mb-0">
                            <thead><tr><th>Amount</th><th>Date</th><th>Payment Method</th><th>Status</th></tr></thead>
                            <tbody>
                                @foreach($stats['payments']->take(5) as $p)
                                    <tr>
                                        <td style="font-weight:600;color:var(--teal);">PKR {{ number_format($p->amount ?? 0) }}</td>
                                        <td class="text-muted">{{ $p->payment_date?->format('d M Y') }}</td>
                                        <td>{{ $p->payment_method }}</td>
                                        <td><span class="badge-status badge-{{ $p->status }}">{{ \App\Models\Payment::labelForVerificationStatus($p->status ?? '') }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
