@extends('layouts.app')
@section('title', 'My Payments')
@section('page-title', 'My Payment History')

@push('styles')
<style>
.child-payments-pagination {
    margin-top: 0.5rem;
    padding: 1rem 1rem 1.25rem;
    width: 100%;
    overflow-x: auto;
    border-top: 1px solid var(--border-soft, #e5eaf0);
}
.child-payments-pagination > nav {
    width: 100%;
    max-width: 100%;
}
.child-payments-pagination .d-none.flex-sm-fill.d-sm-flex.align-items-sm-center.justify-content-sm-between {
    width: 100%;
    gap: 1rem 2rem;
    flex-wrap: wrap;
    align-items: center !important;
}
.child-payments-pagination .d-none.flex-sm-fill.d-sm-flex > div:first-child {
    flex: 1 1 auto;
    min-width: 0;
}
.child-payments-pagination .d-none.flex-sm-fill.d-sm-flex > div:first-child p.small {
    margin-bottom: 0;
    line-height: 1.5;
}
.child-payments-pagination .d-none.flex-sm-fill.d-sm-flex > div:last-child {
    flex: 0 0 auto;
    margin-left: auto;
}
.child-payments-pagination div.d-flex.flex-fill.d-sm-none {
    justify-content: center;
    width: 100%;
}
.child-payments-pagination .pagination {
    flex-wrap: wrap;
    justify-content: center;
    gap: 0.25rem;
    margin-bottom: 0;
}
.child-payments-pagination .page-link {
    border-radius: 8px;
    color: var(--navy, #11517c);
}
.child-payments-pagination .page-item.active .page-link {
    background-color: var(--teal, #008080);
    border-color: var(--teal, #008080);
    color: #fff;
}
</style>
@endpush

@section('content')
<div class="card-frc card-frc--list-page child-payments-page">
    <div class="card-header-frc flex-wrap gap-2">
        <h6 class="card-title-frc mb-0"><i class="fa-solid fa-receipt me-2" style="color:var(--teal);"></i>Payment history @if(!$payments->isEmpty())<span class="text-muted fw-normal" style="font-size:0.85rem;">({{ $payments->total() }})</span>@endif</h6>
        @if($can_upload_fee_slip ?? false)
            <a href="{{ route('child.upload-slip') }}" class="btn-teal btn-view-all" style="white-space:nowrap;"><i class="fa-solid fa-upload"></i> Upload slip</a>
        @endif
    </div>

    @if($payments->isEmpty())
        <div class="empty-state py-5">
            <i class="fa-solid fa-receipt empty-icon"></i>
            <h5>No payments yet</h5>
            <p class="text-muted mb-0">Manual payments recorded by staff and slips you upload will appear here.</p>
        </div>
    @else
        <div class="frc-table-wrap frc-table-wrap--wide table-scroll child-payments-table-wrap">
            <table class="table-frc mb-0 child-payments-table">
                <thead>
                    <tr>
                        <th>Receipt #</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Slip</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($payments as $p)
                        <tr>
                            <td class="child-payments-receipt">{{ $p->hasPrintableReceipt() ? $p->receipt_number : '—' }}</td>
                            <td class="text-amount child-payments-amount">PKR {{ frc_money($p->amount) }}</td>
                            <td class="child-payments-method">{{ \App\Models\Payment::labelForPaymentMethod($p->payment_method) }}</td>
                            <td class="child-payments-date">{{ $p->payment_date?->format('d M Y') }}</td>
                            <td class="child-payments-status">
                                <span class="badge-status badge-{{ $p->status }}" style="font-size:11px;">{{ \App\Models\Payment::labelForVerificationStatus($p->status) }}</span>
                                @if($p->status === 'rejected' && $p->rejection_reason)
                                    <div class="mt-1">
                                        <button
                                            type="button"
                                            class="btn-outline-teal child-payments-action-btn"
                                            data-bs-toggle="modal"
                                            data-bs-target="#paymentRejectReasonModal"
                                            data-reason="{{ e($p->rejection_reason) }}"
                                        >
                                            Reason
                                        </button>
                                    </div>
                                @endif
                            </td>
                            <td class="child-payments-slip">
                                @if($p->payment_slip)
                                    <a href="{{ $p->payment_slip_url }}" target="_blank" rel="noopener" class="btn-outline-teal child-payments-slip-btn" title="Preview slip"><i class="fa-solid fa-image"></i></a>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="child-payments-actions">
                                @if($p->hasPrintableReceipt())
                                    <div class="child-payments-action-btns">
                                        <a href="{{ route('payments.receipt', $p->id) }}" target="_blank" class="btn-outline-teal child-payments-action-btn">Receipt</a>
                                    </div>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($payments->hasPages())
            <div class="child-payments-pagination" aria-label="Payment history pages">
                {{ $payments->onEachSide(1)->links() }}
            </div>
        @endif
    @endif
</div>

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
