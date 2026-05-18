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
        $receiptPrintUrl = $receiptPdfUrl . '&inline=1';
    @endphp
    <button type="button" onclick="printReceipt()" class="btn-teal"><i class="fa-solid fa-print"></i> Print</button>
    <a href="{{ $receiptPdfUrl }}" class="btn-outline-teal"><i class="fa-solid fa-file-pdf"></i> PDF</a>
    <a href="{{ $receiptBackUrl }}" class="btn-outline-teal"><i class="fa-solid fa-arrow-left"></i> Back</a>
</div>

<div class="receipt-print-shell">
    <div class="receipt-card">
        @include('payments.partials.receipt-content')
    </div>
</div>
@push('scripts')
<script>
function printReceipt() {
    var iframe = document.createElement('iframe');
    iframe.setAttribute('title', 'Receipt print');
    iframe.style.cssText = 'position:fixed;width:0;height:0;border:0;opacity:0;pointer-events:none;left:-9999px';
    iframe.src = @json($receiptPrintUrl);
    document.body.appendChild(iframe);
    iframe.onload = function () {
        try {
            iframe.contentWindow.focus();
            iframe.contentWindow.print();
        } finally {
            setTimeout(function () { iframe.remove(); }, 2000);
        }
    };
}
@if(request('autoprint'))
document.addEventListener('DOMContentLoaded', function () {
    setTimeout(printReceipt, 400);
});
@endif
</script>
@endpush
@endsection
