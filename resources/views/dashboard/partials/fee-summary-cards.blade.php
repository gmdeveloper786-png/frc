{{-- Fee totals row: expected, paid, pending, cash, online, pending verification amount. --}}
@php
    $pendingPaymentsHref = ($showPendingVerificationLink ?? true) && (auth()->user()?->hasPermission('verify_payments') ?? false)
        ? route('payments.pending')
        : null;
@endphp
<div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 g-3 mb-4">
    <div class="col">
        <div class="stat-card h-100">
            <div class="stat-icon navy"><i class="fa-solid fa-money-bill-trend-up"></i></div>
            <div class="stat-body">
                <div class="stat-value" style="font-size:16px;color:var(--navy);">{{ frc_pkr($stats['fee_total_expected'] ?? 0) }}</div>
                <div class="stat-label">Total Expected</div>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="stat-card h-100">
            <div class="stat-icon green"><i class="fa-solid fa-circle-check"></i></div>
            <div class="stat-body">
                <div class="stat-value" style="font-size:16px;color:var(--success);">{{ frc_pkr($stats['fee_total_paid'] ?? 0) }}</div>
                <div class="stat-label">Total Collected</div>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="stat-card h-100">
            <div class="stat-icon red"><i class="fa-solid fa-hourglass-half"></i></div>
            <div class="stat-body">
                <div class="stat-value" style="font-size:16px;color:var(--danger);">{{ frc_pkr($stats['fee_pending_overdue'] ?? 0) }}</div>
                <div class="stat-label">Pending</div>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="stat-card h-100">
            <div class="stat-icon teal"><i class="fa-solid fa-money-bills"></i></div>
            <div class="stat-body">
                <div class="stat-value" style="font-size:16px;color:var(--teal-dark);">{{ frc_pkr($stats['fee_cash_received'] ?? 0) }}</div>
                <div class="stat-label">Cash Received</div>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="stat-card h-100">
            <div class="stat-icon purple"><i class="fa-solid fa-mobile-screen"></i></div>
            <div class="stat-body">
                <div class="stat-value" style="font-size:16px;color:#7c3aed;">{{ frc_pkr($stats['fee_online_bank'] ?? 0) }}</div>
                <div class="stat-label">Online/Bank</div>
            </div>
        </div>
    </div>
    <div class="col">
        @if($pendingPaymentsHref)
            <a href="{{ $pendingPaymentsHref }}" class="stat-card-link d-block h-100 text-reset text-decoration-none rounded-3">
        @endif
        <div class="stat-card h-100 {{ $pendingPaymentsHref ? 'stat-card--clickable' : '' }}">
            <div class="stat-icon orange"><i class="fa-solid fa-money-bill-trend-up"></i></div>
            <div class="stat-body">
                <div class="stat-value" style="font-size:16px;color:#e08000;">{{ frc_pkr($stats['pending_verification_amount'] ?? 0) }}</div>
                <div class="stat-label">Pending Verification Amount</div>
            </div>
        </div>
        @if($pendingPaymentsHref)
            </a>
        @endif
    </div>
</div>

