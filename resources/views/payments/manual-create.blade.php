@extends('layouts.app')
@section('title', 'Add Manual Payment')
@section('page-title', 'Record Manual Payment')

@push('styles')
<style>
.manual-payment-page {
    min-width: 0;
    max-width: 100%;
}
.manual-payment-page .form-section,
.manual-payment-page .manual-payment-enrollment-field {
    min-width: 0;
    max-width: 100%;
}
.manual-payment-page .form-frc select#enrollmentSel {
    width: 100%;
    max-width: 100%;
    text-overflow: ellipsis;
}
.manual-payment-page .manual-payment-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    margin-top: 8px;
}
@media (max-width: 575.98px) {
    .frc-main:has(.manual-payment-page) {
        padding-left: 10px;
        padding-right: 10px;
    }
    .manual-payment-page .form-section {
        padding: 16px;
    }
    .manual-payment-page .manual-payment-actions {
        flex-direction: column;
        align-items: stretch;
    }
    .manual-payment-page .manual-payment-actions .btn-teal,
    .manual-payment-page .manual-payment-actions .btn-outline-teal {
        width: 100%;
        justify-content: center;
    }
    .manual-payment-page #enrollInfo > div {
        grid-template-columns: 1fr 1fr !important;
        gap: 10px !important;
    }
}
</style>
@endpush

@section('content')
@php
    $isFinance = auth()->user()->isFinance();
    $manualStoreRoute = $isFinance ? 'finance.payments.manual.store' : 'payments.manual.store';
    $paymentsListRoute = $isFinance ? 'finance.payments' : 'payments.index';
@endphp
<div class="row g-3 justify-content-center manual-payment-page">
    <div class="col-12 col-lg-7">
        <form action="{{ route($manualStoreRoute) }}" method="POST" class="form-frc">
        @csrf
        <div class="form-section">
            <div class="form-section-title"><i class="fa-solid fa-money-bill-wave" style="color:var(--teal);"></i> Payment Details</div>

            <div class="mb-3 manual-payment-enrollment-field">
                <label>Enrollment <span style="color:var(--danger)">*</span></label>
                @if($enrollments->isNotEmpty())
                    <input type="search" id="enrollmentSearch" class="form-control mb-2" placeholder="Search child or enrollment #…" autocomplete="off" aria-controls="enrollmentSel">
                @endif
                <select name="enrollment_id" class="form-control @error('enrollment_id') is-invalid @enderror" id="enrollmentSel" @if($enrollments->isEmpty()) disabled @endif>
                    <option value="">Select Enrollment</option>
                    @foreach($enrollments as $e)
                        @php
                            $collectible = $e->outstandingForSlipUpload();
                            $pending = $e->sumPendingVerificationAmount();
                            $childName = $e->child?->full_name ?? '—';
                            $optionLabel = '#'.$e->id.' — '.$childName.' · '.frc_pkr($collectible);
                            $optionTitle = $optionLabel;
                            if ($pending > 0) {
                                $optionTitle .= ' · '.frc_pkr($pending).' pending verification';
                            }
                        @endphp
                        <option value="{{ $e->id }}" data-search="{{ strtolower('#'.$e->id.' '.$childName) }}" title="{{ $optionTitle }}" {{ (old('enrollment_id', request('enrollment_id')) == $e->id) ? 'selected' : '' }}>
                            {{ $optionLabel }}
                        </option>
                    @endforeach
                </select>
                @if($enrollments->isEmpty())
                    <p class="small text-muted mt-2 mb-0">No enrollments with an outstanding balance right now.</p>
                @else
                    <p class="small text-muted mt-2 mb-0">Showing up to {{ $enrollments->count() }} enrollments with balance due. Use search to find a child quickly.</p>
                @endif
                @error('enrollment_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div id="enrollInfo" style="display:none;background:var(--bg-light);border-radius:10px;padding:12px;margin-bottom:16px;font-size:13px;"></div>

            <div class="row g-3">
                <div class="col-12">
                    <label>Amount (PKR) <span style="color:var(--danger)">*</span></label>
                    <input type="text" name="amount" value="{{ old('amount') }}" class="form-control @error('amount') is-invalid @enderror" inputmode="numeric" pattern="[0-9]*" maxlength="9" placeholder="Enter amount">
                    @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-12 col-lg-6">
                    <label>Payment Method</label>
                    <input type="hidden" name="payment_method" value="cash">
                    <div class="form-control" style="background:var(--bg-light);cursor:default;border-color:var(--border-soft);color:var(--navy);font-weight:500;">
                        Cash <span style="color:var(--text-muted);font-weight:normal;font-size:12px;">(manual desk payment only)</span>
                    </div>
                </div>
                <div class="col-12 col-lg-6">
                    <label>Payment Date <span style="color:var(--danger)">*</span></label>
                    <input type="date" name="payment_date" value="{{ old('payment_date', date('Y-m-d')) }}" class="form-control @error('payment_date') is-invalid @enderror">
                    @error('payment_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-12">
                    <label>Notes</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Optional notes">{{ old('notes') }}</textarea>
                </div>
            </div>
        </div>

        <div class="manual-payment-actions">
            <a href="{{ route($paymentsListRoute) }}" class="btn-outline-teal">Cancel</a>
            <button type="submit" class="btn-teal">
                <i class="fa-solid fa-money-bill-wave"></i> Record Payment
            </button>
        </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce }}">
const enrollments = @json($enrollmentLookup);

function filterEnrollmentOptions() {
    const input = document.getElementById('enrollmentSearch');
    const select = document.getElementById('enrollmentSel');
    if (!input || !select) return;
    const q = input.value.trim().toLowerCase();
    Array.from(select.options).forEach(function (opt, idx) {
        if (idx === 0) {
            opt.hidden = false;
            return;
        }
        const hay = opt.getAttribute('data-search') || opt.textContent.toLowerCase();
        opt.hidden = q !== '' && !hay.includes(q);
    });
}

function loadEnrollmentInfo(id) {
    const el = document.getElementById('enrollInfo');
    if (!id || !enrollments[id]) { el.style.display = 'none'; return; }
    const e = enrollments[id];
    const pending = Number(e.pending_verification) || 0;
    const pendingHtml = pending > 0
        ? `<div><div style="color:var(--text-muted)">Pending verification</div><strong style="color:#e08000;">PKR ${pending.toLocaleString()}</strong></div>`
        : '';
    el.innerHTML = `
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:8px;">
            <div><div style="color:var(--text-muted)">Total fee</div><strong>PKR ${Number(e.final_total).toLocaleString()}</strong></div>
            <div><div style="color:var(--text-muted)">Paid (verified)</div><strong style="color:var(--success)">PKR ${Number(e.paid_amount).toLocaleString()}</strong></div>
            ${pendingHtml}
            <div><div style="color:var(--text-muted)">You can record (max)</div><strong style="color:var(--danger)">PKR ${Number(e.collectible_amount).toLocaleString()}</strong></div>
        </div>
    `;
    el.style.display = 'block';
    document.querySelector('[name=amount]').value = Math.round(parseFloat(e.collectible_amount) || 0);
}

const searchInput = document.getElementById('enrollmentSearch');
if (searchInput) {
    searchInput.addEventListener('input', filterEnrollmentOptions);
}

const sel = document.getElementById('enrollmentSel');
if (sel) {
    sel.addEventListener('change', function () { loadEnrollmentInfo(this.value); });
    if (sel.value) loadEnrollmentInfo(sel.value);
}

document.querySelector('input[name="amount"]')?.addEventListener('input', function () {
    this.value = String(this.value || '').replace(/\D/g, '');
});
</script>
@endpush
