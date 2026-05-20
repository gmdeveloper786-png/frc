@extends('layouts.app')
@section('title', 'Pending Verifications')
@section('page-title', 'Pending Payment Verifications')

@push('styles')
<style>
.payments-pending-pagination {
    margin-top: 0;
    padding: 1rem 1rem 1.25rem;
    width: 100%;
    overflow-x: auto;
    border-top: 1px solid var(--border-soft, #e5eaf0);
}
.payments-pending-pagination > nav {
    width: 100%;
    max-width: 100%;
}
.payments-pending-pagination .d-none.flex-sm-fill.d-sm-flex.align-items-sm-center.justify-content-sm-between {
    width: 100%;
    gap: 1rem 2rem;
    flex-wrap: wrap;
    align-items: center !important;
}
.payments-pending-pagination .d-none.flex-sm-fill.d-sm-flex > div:first-child {
    flex: 1 1 auto;
    min-width: 0;
}
.payments-pending-pagination .d-none.flex-sm-fill.d-sm-flex > div:first-child p.small {
    margin-bottom: 0;
    line-height: 1.5;
}
.payments-pending-pagination .d-none.flex-sm-fill.d-sm-flex > div:last-child {
    flex: 0 0 auto;
    margin-left: auto;
}
.payments-pending-pagination div.d-flex.flex-fill.d-sm-none {
    justify-content: center;
    width: 100%;
}
.payments-pending-pagination .pagination {
    flex-wrap: wrap;
    justify-content: center;
    gap: 0.25rem;
    margin-bottom: 0;
}
.payments-pending-pagination .page-link {
    border-radius: 8px;
    min-width: 2.25rem;
    text-align: center;
    color: var(--navy, #11517c);
}
.payments-pending-pagination .page-item.active .page-link {
    background-color: var(--teal, #008080);
    border-color: var(--teal, #008080);
    color: #fff;
}
.payments-pending-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: center;
}
.payments-pending-actions form {
    display: inline-flex;
    margin: 0;
}
.payments-pending-actions .pp-action-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    white-space: nowrap;
    padding: 6px 12px;
    font-size: 12px;
    font-family: 'Poppins', sans-serif;
    font-weight: 500;
    line-height: 1;
    border: none;
    border-radius: var(--radius-btn, 8px);
    cursor: pointer;
}
.payments-pending-actions .pp-action-btn--verify {
    background: var(--success);
    color: #fff;
}
.payments-pending-actions .pp-action-btn--reject {
    background: var(--danger);
    color: #fff;
}
</style>
@endpush

@section('content')
@php
    $paymentVerifyRoute = auth()->user()->isFinance() ? 'finance.payments.verify' : 'payments.verify';
    $paymentRejectRoute = auth()->user()->isFinance() ? 'finance.payments.reject' : 'payments.reject';
@endphp
<div class="card-frc card-frc--list-page">
    <div class="card-header-frc">
        <h6 class="card-title-frc">
            <i class="fa-solid fa-clock me-2" style="color:var(--warning);"></i>
            Pending Verifications ({{ $payments->total() }})
        </h6>
    </div>
    @if($payments->isEmpty())
        <div class="empty-state">
            <i class="fa-solid fa-circle-check empty-icon" style="color:var(--success);"></i>
            <h5>No Pending Verifications</h5>
            <p>All submitted payment slips have been reviewed.</p>
        </div>
    @else
        <div class="frc-table-wrap frc-table-wrap--wide table-scroll">
            <table class="table-frc mb-0">
                <thead><tr><th>Child</th><th>Enrollment</th><th>Amount</th><th>Slip</th><th>Submitted</th><th>Actions</th></tr></thead>
                <tbody>
                    @foreach($payments as $p)
                        <tr>
                            <td>
                                <div style="font-weight:600;">{{ $p->child?->full_name }}</div>
                                <div style="font-size:11px;color:var(--text-muted);">{{ $p->child?->phone_number }}</div>
                            </td>
                            <td style="font-size:13px;">
                                <a href="{{ route('enrollments.show', $p->enrollment_id) }}" style="color:var(--teal);">
                                    #{{ $p->enrollment_id }} — {{ frc_pkr($p->enrollment?->final_total) }}
                                </a>
                            </td>
                            <td class="text-amount" style="font-weight:700;color:var(--navy);">{{ frc_pkr($p->amount) }}</td>
                            <td>
                                @if($p->payment_slip)
                                    <a href="{{ $p->payment_slip_url }}" target="_blank" class="btn-outline-teal" style="font-size:12px;padding:4px 10px;">
                                        <i class="fa-solid fa-image"></i> View Slip
                                    </a>
                                @else
                                    <span style="color:var(--text-muted);">No slip</span>
                                @endif
                            </td>
                            <td class="text-nowrap" style="font-size:13px;color:var(--text-muted);">{{ $p->created_at?->format('d M Y, H:i') }}</td>
                            <td>
                                <div class="payments-pending-actions">
                                    <form action="{{ route($paymentVerifyRoute, $p->id) }}" method="POST">@csrf
                                        <button type="submit" class="pp-action-btn pp-action-btn--verify">
                                            <i class="fa-solid fa-check"></i> Verify
                                        </button>
                                    </form>
                                    <button type="button" class="pp-action-btn pp-action-btn--reject"
                                        data-bs-toggle="modal" data-bs-target="#rejectModal{{ $p->id }}">
                                        <i class="fa-solid fa-xmark"></i> Reject
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($payments->hasPages())
            <div class="payments-pending-pagination" aria-label="Pending verifications pages">
                {{ $payments->onEachSide(1)->links() }}
            </div>
        @endif
    @endif
</div>

@foreach($payments as $p)
<div class="modal fade" id="rejectModal{{ $p->id }}" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:16px;">
            <div class="modal-header" style="border-bottom:1px solid var(--border-soft);">
                <h6 class="modal-title" style="font-family:'Poppins',sans-serif;color:var(--navy);">Reject Payment — {{ $p->child?->full_name }}</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route($paymentRejectRoute, $p->id) }}" method="POST">
                @csrf
                <div class="modal-body form-frc">
                    <label>Rejection Reason <span style="color:var(--danger)">*</span></label>
                    <textarea name="rejection_reason" class="form-control" rows="3" required placeholder="Explain why this payment is rejected..."></textarea>
                </div>
                <div class="modal-footer" style="border-top:1px solid var(--border-soft);">
                    <button type="button" class="btn-outline-teal" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-navy">Confirm Reject</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach
@endsection

