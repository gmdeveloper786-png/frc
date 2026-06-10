@extends('layouts.app')
@section('title', 'Payment Receipt')
@section('page-title', 'Payment Receipt')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/receipt.css') }}">
@endpush

@section('content')
<div class="no-print receipt-actions" style="display:flex;flex-wrap:wrap;gap:10px;margin-bottom:20px;">
    @php
        $receiptBackUrl = route('payments.index');
        $receiptPdfUrl = route('payments.receipt', $payment->id) . '?pdf=1';
        if (auth()->user()?->isChild()) {
            $receiptBackUrl = route('child.payments');
        } elseif (auth()->user()?->isFinance()) {
            $receiptBackUrl = route('finance.payments');
            $receiptPdfUrl = route('finance.payments.receipt', $payment->id) . '?pdf=1';
        }
    @endphp
    <button type="button" data-print-page class="btn-teal"><i class="fa-solid fa-print"></i> Print</button>
    <a href="{{ $receiptPdfUrl }}" class="btn-outline-teal"><i class="fa-solid fa-file-pdf"></i> PDF</a>
    <a href="{{ $receiptBackUrl }}" class="btn-outline-teal"><i class="fa-solid fa-arrow-left"></i> Back</a>
</div>

<div class="receipt-print-shell">
    <div class="receipt-card">
        @include('payments.partials.receipt-content')
    </div>
</div>
@if(request('autoprint'))
@push('scripts')
<script nonce="{{ $cspNonce }}">
document.addEventListener('DOMContentLoaded', function () {
    setTimeout(function () {
        window.print();
    }, 400);
});
</script>
@endpush
@endif
@endsection
