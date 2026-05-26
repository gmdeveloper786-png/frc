@extends('layouts.app')
@section('title', 'Add Manual Payment')
@section('page-title', 'Record Manual Payment')

@section('content')
@php
    $isFinance = auth()->user()->isFinance();
    $manualStoreRoute = $isFinance ? 'finance.payments.manual.store' : 'payments.manual.store';
    $paymentsListRoute = $isFinance ? 'finance.payments' : 'payments.index';
@endphp
<div class="row g-3 justify-content-center">
    <div class="col-md-7">
        <form action="{{ route($manualStoreRoute) }}" method="POST" class="form-frc">
        @csrf
        <div class="form-section">
            <div class="form-section-title"><i class="fa-solid fa-money-bill-wave" style="color:var(--teal);"></i> Payment Details</div>

            <div class="mb-3">
                <label>Enrollment <span style="color:var(--danger)">*</span></label>
                @if($enrollments->isNotEmpty())
                    <input type="search" id="enrollmentSearch" class="form-control mb-2" placeholder="Search by child name or enrollment #…" autocomplete="off" aria-controls="enrollmentSel">
                @endif
                <select name="enrollment_id" class="form-control @error('enrollment_id') is-invalid @enderror" id="enrollmentSel" onchange="loadEnrollmentInfo(this.value)" @if($enrollments->isEmpty()) disabled @endif>
                    <option value="">Select Enrollment</option>
                    @foreach($enrollments as $e)
                        @php $remaining = (float) $e->getRawOriginal('remaining_amount'); @endphp
                        <option value="{{ $e->id }}" data-search="{{ strtolower('#'.$e->id.' '.$e->child?->full_name) }}" {{ (old('enrollment_id', request('enrollment_id')) == $e->id) ? 'selected' : '' }}>
                            #{{ $e->id }} — {{ $e->child?->full_name }} (PKR {{ frc_money($remaining) }} remaining)
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
                <div class="col-md-6">
                    <label>Payment Method</label>
                    <input type="hidden" name="payment_method" value="cash">
                    <div class="form-control" style="background:var(--bg-light);cursor:default;border-color:var(--border-soft);color:var(--navy);font-weight:500;">
                        Cash <span style="color:var(--text-muted);font-weight:normal;font-size:12px;">(manual desk payment only)</span>
                    </div>
                </div>
                <div class="col-md-6">
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

        <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:8px;">
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
<script>
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
    el.innerHTML = `
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;">
            <div><div style="color:var(--text-muted)">Total Fee</div><strong>PKR ${Number(e.final_total).toLocaleString()}</strong></div>
            <div><div style="color:var(--text-muted)">Paid</div><strong style="color:var(--success)">PKR ${Number(e.paid_amount).toLocaleString()}</strong></div>
            <div><div style="color:var(--text-muted)">Remaining</div><strong style="color:var(--danger)">PKR ${Number(e.remaining_amount).toLocaleString()}</strong></div>
        </div>
    `;
    el.style.display = 'block';
    // Auto-fill amount with remaining
    document.querySelector('[name=amount]').value = Math.round(parseFloat(e.remaining_amount) || 0);
}

const searchInput = document.getElementById('enrollmentSearch');
if (searchInput) {
    searchInput.addEventListener('input', filterEnrollmentOptions);
}

const sel = document.getElementById('enrollmentSel');
if (sel && sel.value) loadEnrollmentInfo(sel.value);

document.querySelector('input[name="amount"]')?.addEventListener('input', function () {
    this.value = String(this.value || '').replace(/\D/g, '');
});
</script>
@endpush
