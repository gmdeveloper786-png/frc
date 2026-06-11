@extends('layouts.app')
@section('title', 'Payments')
@section('page-title', 'Payments')

@push('styles')
<style>
.payments-index-pagination {
    margin-top: 0;
    padding: 1rem 1rem 1.25rem;
    width: 100%;
    overflow-x: auto;
    border-top: 1px solid var(--border-soft, #e5eaf0);
}
.payments-index-pagination > nav {
    width: 100%;
    max-width: 100%;
}
.payments-index-pagination .d-none.flex-sm-fill.d-sm-flex.align-items-sm-center.justify-content-sm-between {
    width: 100%;
    gap: 1rem 2rem;
    flex-wrap: wrap;
    align-items: center !important;
}
.payments-index-pagination .d-none.flex-sm-fill.d-sm-flex > div:first-child {
    flex: 1 1 auto;
    min-width: 0;
}
.payments-index-pagination .d-none.flex-sm-fill.d-sm-flex > div:first-child p.small {
    margin-bottom: 0;
    line-height: 1.5;
}
.payments-index-pagination .d-none.flex-sm-fill.d-sm-flex > div:last-child {
    flex: 0 0 auto;
    margin-left: auto;
}
.payments-index-pagination div.d-flex.flex-fill.d-sm-none {
    justify-content: center;
    width: 100%;
}
.payments-index-pagination .pagination {
    flex-wrap: wrap;
    justify-content: center;
    gap: 0.25rem;
    margin-bottom: 0;
}
.payments-index-pagination .page-link {
    border-radius: 8px;
    min-width: 2.25rem;
    text-align: center;
    color: var(--navy, #11517c);
}
.payments-index-pagination .page-item.active .page-link {
    background-color: var(--teal, #008080);
    border-color: var(--teal, #008080);
    color: #fff;
}
</style>
@endpush

@section('content')
@php
    $isFinance = auth()->user()->isFinance();
    $paymentsIndexRoute = $isFinance ? 'finance.payments' : 'payments.index';
    $paymentsManualCreateRoute = $isFinance ? 'finance.payments.manual.create' : 'payments.manual.create';
    $paymentsReceiptRoute = $isFinance ? 'finance.payments.receipt' : 'payments.receipt';
    $paymentsVerifyRoute = $isFinance ? 'finance.payments.verify' : 'payments.verify';
    $paymentsRejectRoute = $isFinance ? 'finance.payments.reject' : 'payments.reject';
@endphp
<div class="card-frc card-frc--list-page">
    <div class="card-header-frc">
        <h6 class="card-title-frc"><i class="fa-solid fa-receipt me-2" style="color:var(--teal);"></i>Payments ({{ $payments->total() }})</h6>
        @if(auth()->user()->hasPermission('manage_payments'))
            <a href="{{ route($paymentsManualCreateRoute) }}" class="btn-teal btn-view-all" style="white-space:nowrap; min-width:auto;"><i class="fa-solid fa-plus"></i> Manual Payment</a>
        @endif
    </div>
    <form method="GET" class="p-3 border-bottom list-filters" style="border-color:var(--border-soft)!important;">
        <div class="row g-2 align-items-end form-frc">
            <div class="col-12 col-md-4">
                <label class="form-label small text-muted mb-1">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Receipt# or child name...">
            </div>
            <div class="col-12 col-sm-6 col-md-4">
                <label class="form-label small text-muted mb-1">Payment Status</label>
                <select name="enrollment_payment_status" class="form-control">
                    <option value="">All</option>
                    @foreach(['unpaid','partial_paid','fully_paid'] as $s)
                        <option value="{{ $s }}" {{ request('enrollment_payment_status') == $s ? 'selected' : '' }}>{{ \App\Models\Payment::labelForEnrollmentPaymentStatus($s) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-sm-6 col-md-4">
                <label class="form-label small text-muted mb-1">Verification</label>
                <select name="verification_status" class="form-control">
                    <option value="">All</option>
                    @foreach(['pending_verification','paid','rejected','cancelled'] as $s)
                        <option value="{{ $s }}" {{ request('verification_status', request('status')) == $s ? 'selected' : '' }}>{{ \App\Models\Payment::labelForVerificationStatus($s) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-sm-6 col-md-4">
                <label class="form-label small text-muted mb-1">Method</label>
                <select name="payment_method" class="form-control">
                    <option value="">All</option>
                    @foreach(['cash','bank_transfer','easypaisa','jazzcash','card','other'] as $m)
                        <option value="{{ $m }}" {{ request('payment_method') == $m ? 'selected' : '' }}>{{ \App\Models\Payment::labelForPaymentMethod($m) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-sm-6 col-md-4">
                <label class="form-label small text-muted mb-1">From</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control">
            </div>
            <div class="col-12 col-sm-6 col-md-4">
                <label class="form-label small text-muted mb-1">To</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control">
            </div>
            <div class="col-12 filter-actions">
                <button type="submit" class="btn-teal">Filter</button>
                @if(request()->hasAny(['search','verification_status','status','enrollment_payment_status','payment_method','date_from','date_to']))
                    <a href="{{ route($paymentsIndexRoute) }}" class="btn-outline-teal" style="justify-content:center;">Clear</a>
                @endif
            </div>
        </div>
    </form>
    @if($payments->isEmpty())
        <div class="empty-state"><i class="fa-solid fa-receipt empty-icon"></i><h5>No Payments Found</h5></div>
    @else
        <div class="frc-table-wrap frc-table-wrap--wide table-scroll">
            <table class="table-frc mb-0">
                <thead><tr><th>Receipt#</th><th>GR No</th><th>Enroll#</th><th>Child</th><th>Total Fee</th><th>Paid</th><th>Remaining</th><th>Payment</th><th>Amount</th><th>Verification</th><th>Method</th><th>Date</th><th>Slip</th><th>Actions</th></tr></thead>
                <tbody>
                    @foreach($payments as $p)
                        <tr>
                            <td style="font-weight:500;font-family:'Poppins',sans-serif;font-size:13px;color:var(--navy); white-space:nowrap;">{{ $p->hasPrintableReceipt() ? $p->receipt_number : '—' }}</td>
                            <td style="font-weight:500;font-family:'Poppins',sans-serif;font-size:13px;color:var(--navy); white-space:nowrap;">{{ $p->child?->gr_number ?? '—' }}</td>
                            <td style="font-weight:500;font-family:'Poppins',sans-serif;font-size:13px;color:var(--navy); white-space:nowrap;"> <a href="{{ route('enrollments.show', $p->enrollment?->id) }}" style="color:var(--navy);text-decoration:underline;">#{{ $p->enrollment?->id ?? '—' }}</a></td>
                            <td style="white-space:nowrap;">
                                <div style="font-weight:500;"><a href="{{ route('children.show', $p->child->id) }}" style="color:var(--navy);text-decoration:underline;">{{ $p->child?->full_name }}</a></div>
                            </td>
                            <td style="white-space:nowrap;">{{ frc_pkr($p->enrollment?->final_total) }}</td>
                            <td style="color:var(--success); white-space:nowrap;">{{ frc_pkr($p->enrollment?->paid_amount) }}</td>
                            <td style="color:var(--danger); white-space:nowrap;">{{ frc_pkr($p->enrollment?->remaining_amount) }}</td>
                            <td style="white-space:nowrap;"><span class="badge-status badge-{{ $p->enrollment?->payment_status }}">{{ \App\Models\Payment::labelForEnrollmentPaymentStatus($p->enrollment?->payment_status) }}</span></td>
                            <td class="text-amount" style="font-weight:600;color:var(--teal); white-space:nowrap;">{{ frc_pkr($p->amount) }}</td>
                            <td style="white-space:nowrap;">
                                <span class="badge-status badge-{{ $p->status }}">{{ \App\Models\Payment::labelForVerificationStatus($p->status) }}</span>
                                @if($p->status === 'rejected' && filled($p->rejection_reason))
                                    <div class="mt-1">
                                        <button
                                            type="button"
                                            class="btn-outline-teal"
                                            style="font-size:11px;padding:3px 8px;"
                                            data-bs-toggle="modal"
                                            data-bs-target="#paymentRejectReasonModal"
                                            data-reason="{{ e($p->rejection_reason) }}"
                                        >
                                            Reason
                                        </button>
                                    </div>
                                @endif
                            </td>
                            <td style="font-size:13px; white-space:nowrap;">{{ \App\Models\Payment::labelForPaymentMethod($p->payment_method) }}</td>
                            <td style="font-size:13px;color:var(--text-muted); white-space:nowrap;">{{ $p->payment_date?->format('d M Y') }}</td>
                            <td style="white-space:nowrap;">
                                @if($p->payment_slip)
                                    <a href="{{ $p->payment_slip_url }}" target="_blank" class="btn-outline-teal" style="font-size:12px;padding:3px 8px;"><i class="fa-solid fa-image"></i></a>
                                @else
                                    <span style="color:var(--text-muted);font-size:12px;">—</span>
                                @endif
                            </td>
                           
                            <td style="white-space:nowrap;">
                                <div style="display:flex;flex-wrap:wrap;gap:6px;align-items:center;">
                                        @if($p->hasPrintableReceipt())
                                            <a href="{{ route($paymentsReceiptRoute, $p->id) }}" class="btn-outline-teal" target="_blank" style="font-size:12px;padding:4px 10px;">Receipt</a>
                                    @elseif($p->status === 'pending_verification' && auth()->user()->hasPermission('verify_payments'))
                                        <form action="{{ route($paymentsVerifyRoute, $p->id) }}" method="POST" style="display:inline-flex;margin:0;">@csrf
                                            <button type="submit" style="display:inline-flex;align-items:center;justify-content:center;background:var(--success);color:#fff;border:none;border-radius:8px;padding:6px 10px;cursor:pointer;font-size:12px;line-height:1;" data-confirm="Verify?" title="Verify">
                                                <i class="fa-solid fa-check"></i>
                                            </button>
                                        </form>
                                        <button type="button" style="display:inline-flex;align-items:center;justify-content:center;background:var(--danger);color:#fff;border:none;border-radius:8px;padding:6px 10px;cursor:pointer;font-size:12px;line-height:1;"
                                            data-bs-toggle="modal" data-bs-target="#rejectModal{{ $p->id }}" title="Reject">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($payments->hasPages())
            <div class="payments-index-pagination" aria-label="Payments list pages">
                {{ $payments->onEachSide(1)->links() }}
            </div>
        @endif
    @endif
</div>

@foreach($payments as $p)
    @if($p->status === 'pending_verification')
        <div class="modal fade" id="rejectModal{{ $p->id }}" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content" style="border-radius:16px;">
                    <div class="modal-header" style="border-bottom:1px solid var(--border-soft);">
                        <h6 class="modal-title" style="font-family:'Poppins',sans-serif;color:var(--navy);">Reject Payment</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="{{ route($paymentsRejectRoute, $p->id) }}" method="POST">
                        @csrf
                        <div class="modal-body form-frc">
                            <label>Rejection Reason <span style="color:var(--danger)">*</span></label>
                            <textarea name="rejection_reason" class="form-control" rows="3" required></textarea>
                        </div>
                        <div class="modal-footer" style="border-top:1px solid var(--border-soft);">
                            <button type="button" class="btn-outline-teal" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn-navy">Reject</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endforeach

<div class="modal fade" id="paymentRejectReasonModal" tabindex="-1" aria-labelledby="paymentRejectReasonModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:14px;border:1px solid var(--border-soft);overflow:hidden;">
            <div class="modal-header" style="background:#fde8e8;border-bottom:1px solid var(--border-soft);">
                <h5 class="modal-title" id="paymentRejectReasonModalLabel" style="font-family:'Poppins',sans-serif;font-weight:600;color:var(--danger);font-size:17px;">
                    Rejection Reason
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0" id="paymentRejectReasonText" style="white-space:pre-wrap;color:var(--text-dark);"></p>
            </div>
            <div class="modal-footer" style="border-top:1px solid var(--border-soft);">
                <button type="button" class="btn-outline-teal" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce }}">
(function () {
    const modal = document.getElementById('paymentRejectReasonModal');
    if (!modal) return;
    modal.addEventListener('show.bs.modal', function (event) {
        const trigger = event.relatedTarget;
        const text = modal.querySelector('#paymentRejectReasonText');
        if (!text) return;
        text.textContent = trigger?.getAttribute('data-reason') || 'No reason provided.';
    });
})();
</script>
@endpush
