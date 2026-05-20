@extends('layouts.app')
@section('title', 'Payment Detail')
@section('page-title', 'Payment Detail')

@section('content')
<div class="row g-3 justify-content-center">
    <div class="col-lg-8">
        <div class="card-frc mb-3">
            <div class="card-header-frc flex-wrap gap-2">
                <h6 class="card-title-frc mb-0"><i class="fa-solid fa-file-invoice-dollar me-2" style="color:var(--teal);"></i>Payment #{{ $payment->id }}</h6>
                <span class="badge-status badge-{{ $payment->status }}" style="font-size:11px;">{{ \App\Models\Payment::labelForVerificationStatus($payment->status) }}</span>
            </div>
            <div class="p-3 p-md-4">
                <dl class="row mb-0 small">
                    <dt class="col-sm-4 text-muted mb-2">Receipt number</dt>
                    <dd class="col-sm-8 mb-2 fw-semibold" style="color:var(--navy);">{{ $payment->hasPrintableReceipt() ? $payment->receipt_number : '—' }}</dd>

                    <dt class="col-sm-4 text-muted mb-2">Amount</dt>
                    <dd class="col-sm-8 mb-2 fw-semibold" style="color:var(--teal-dark);">PKR {{ frc_money($payment->amount) }}</dd>

                    <dt class="col-sm-4 text-muted mb-2">Method</dt>
                    <dd class="col-sm-8 mb-2">{{ \App\Models\Payment::labelForPaymentMethod($payment->payment_method) }}</dd>

                    <dt class="col-sm-4 text-muted mb-2">Payment date</dt>
                    <dd class="col-sm-8 mb-2">{{ $payment->payment_date?->format('d M Y') ?? '—' }}</dd>

                    <dt class="col-sm-4 text-muted mb-2">Enrollment</dt>
                    <dd class="col-sm-8 mb-2">
                        @if($payment->enrollment)
                            <a href="{{ route('enrollments.show', $payment->enrollment_id) }}" style="color:var(--navy);text-decoration:underline;">#{{ $payment->enrollment_id }}</a>
                            — {{ $payment->enrollment->child?->full_name ?? '—' }}
                        @else
                            —
                        @endif
                    </dd>

                    <dt class="col-sm-4 text-muted mb-2">Child</dt>
                    <dd class="col-sm-8 mb-2">{{ $payment->child?->full_name ?? '—' }}</dd>

                    @if($payment->transaction_reference)
                        <dt class="col-sm-4 text-muted mb-2">Transaction ref.</dt>
                        <dd class="col-sm-8 mb-2">{{ $payment->transaction_reference }}</dd>
                    @endif

                    <dt class="col-sm-4 text-muted mb-2">Recorded / verified</dt>
                    <dd class="col-sm-8 mb-2">
                        @if($payment->receivedBy)
                            {{ $payment->receivedBy->full_name }}
                        @else
                            —
                        @endif
                        @if($payment->verified_at)
                            <span class="text-muted"> · {{ $payment->verified_at->format('d M Y h:i A') }}</span>
                        @endif
                    </dd>

                    @if(! empty($payment->notes))
                        <dt class="col-sm-4 text-muted mb-2">Notes</dt>
                        <dd class="col-sm-8 mb-2" style="white-space:pre-wrap;">{{ $payment->notes }}</dd>
                    @endif

                    @if($payment->status === 'rejected' && filled($payment->rejection_reason))
                        <dt class="col-sm-4 text-muted mb-2">Rejection reason</dt>
                        <dd class="col-sm-8 mb-2" style="white-space:pre-wrap;color:var(--danger);">{{ $payment->rejection_reason }}</dd>
                    @endif
                </dl>

                <div class="mt-4 d-flex flex-wrap gap-2">
                    @if($payment->hasPrintableReceipt())
                        <a href="{{ route('payments.receipt', $payment->id) }}" class="btn-outline-teal" style="font-size:13px;"><i class="fa-solid fa-receipt"></i> View receipt</a>
                        <a href="{{ route('payments.receipt', ['id' => $payment->id, 'pdf' => 1]) }}" class="btn-outline-teal" style="font-size:13px;"><i class="fa-solid fa-file-pdf"></i> Download receipt PDF</a>
                    @endif
                    @if($payment->payment_slip)
                        <a href="{{ $payment->payment_slip_url }}" target="_blank" rel="noopener" class="btn-outline-teal" style="font-size:13px;"><i class="fa-solid fa-paperclip"></i> View slip</a>
                    @endif
                    @if($payment->enrollment_id)
                        <a href="{{ route('enrollments.show', $payment->enrollment_id) }}" class="btn-teal" style="font-size:13px;"><i class="fa-solid fa-arrow-left"></i> Back to enrollment</a>
                    @endif
                    <a href="{{ route('payments.index') }}" class="btn-outline-teal" style="font-size:13px;"><i class="fa-solid fa-list"></i> All payments</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
