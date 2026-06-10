@extends('layouts.app')
@section('title', 'Upload Payment Slip')
@section('page-title', 'Upload Payment Slip')

@section('content')
@php
    $pickerList = isset($picker) ? $picker : collect();
    $eligibleList = isset($eligible) ? $eligible : collect();
    $repopulateForm = $errors->any();
@endphp
<div class="row g-3 justify-content-center">
    <div class="col-lg-7 col-md-9">
        <div class="card-frc">
            <h6 style="font-family:'Poppins',sans-serif;color:var(--navy);margin-bottom:16px;">
                <i class="fa-solid fa-upload me-2" style="color:var(--teal);"></i>Fee payment — {{ auth()->user()->full_name }}
            </h6>

            @if(session('success'))
                <div class="alert-frc success mb-3">
                    <i class="fa-solid fa-circle-check"></i>
                    <div>{{ session('success') }}</div>
                </div>
            @endif

            @if(! ($hasVisibleEnrollments ?? false))
                <div class="empty-state" style="padding:32px 20px;">
                    <i class="fa-solid fa-file-contract empty-icon"></i>
                    <h5>No enrollment</h5>
                    <p class="mb-0">You don’t have an approved enrollment yet. Please contact the administration.</p>
                </div>
            @elseif($pickerList->isEmpty())
                @if($pendingOnlyEnrollment ?? null)
                    <div style="background:#fff4e5;border-radius:14px;padding:18px 20px;margin-bottom:20px;border:1px solid #ffe0b2;">
                        <div style="display:flex;gap:12px;align-items:flex-start;">
                            <i class="fa-solid fa-hourglass-half" style="color:#c77800;font-size:22px;margin-top:2px;"></i>
                            <div>
                                <strong style="color:var(--navy);display:block;margin-bottom:6px;">Payment slip awaiting verification</strong>
                                <span style="font-size:13px;color:var(--text-muted);">
                                    {{ $pendingOnlyEnrollment->service?->name ?? 'Programme' }} (Enrollment #{{ $pendingOnlyEnrollment->id }}):
                                    {{ frc_pkr($pendingOnlyEnrollment->sumPendingVerificationAmount()) }} is pending finance verification.
                                    No further slip is needed for this balance until it is approved or rejected.
                                </span>
                            </div>
                        </div>
                    </div>
                @else
                <div style="background:var(--teal-light);border-radius:14px;padding:18px 20px;margin-bottom:20px;border:1px solid rgba(0,128,128,.15);">
                    <div style="display:flex;gap:12px;align-items:flex-start;">
                        <i class="fa-solid fa-circle-check" style="color:var(--teal);font-size:22px;margin-top:2px;"></i>
                        <div>
                            <strong style="color:var(--navy);display:block;margin-bottom:6px;">All programme fees are fully paid.</strong>
                            <span style="font-size:13px;color:var(--text-muted);">You do not need to upload a payment slip for any of your active programmes. If you have more than one enrollment, each is billed separately — when every one shows as fully paid, no slip is required.</span>
                        </div>
                    </div>
                </div>
                @endif
            @else
                @if($pickerList->count() > 1)
                    <form method="GET" action="{{ route('child.upload-slip') }}" class="form-frc mb-4" id="enrollmentPickForm">
                        <label class="form-label" style="font-weight:600;color:var(--navy);">Which programme are you paying for? <span class="text-danger">*</span></label>
                        <p class="text-muted small mb-2">Each enrollment has its own fee. Pick the programme for this slip.</p>
                        <select name="enrollment_id" class="form-control" data-auto-submit-form="enrollmentPickForm">
                            @foreach($pickerList as $opt)
                                @php
                                    $optUploadable = $opt->outstandingForSlipUpload();
                                    $optPending = $opt->sumPendingVerificationAmount();
                                @endphp
                                <option value="{{ $opt->id }}" @selected($enrollment && (int) $enrollment->id === (int) $opt->id)>
                                    #{{ $opt->id }} — {{ $opt->service?->name ?? 'Programme' }} —
                                    @if($optUploadable > 0)
                                        pay up to {{ frc_pkr($optUploadable) }}
                                    @else
                                        {{ frc_pkr($optPending) }} pending verification
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </form>
                @endif

                @if($canUpload && $enrollment)
                    <div class="mb-3" style="font-size:13px;color:var(--navy);">
                        <strong>{{ $enrollment->service?->name ?? 'Programme' }}</strong>
                        <span class="text-muted">· Enrollment #{{ $enrollment->id }} · {{ $enrollment->branch?->name ?? '—' }}</span>
                    </div>
                    <div style="background:var(--bg-light);border-radius:14px;padding:16px;margin-bottom:20px;">
                        <div class="row g-3" style="font-size:13px;">
                            <div class="col-sm-6"><span class="text-muted d-block mb-1">Total fee</span><strong class="text-navy">PKR {{ frc_money($enrollment->final_total) }}</strong></div>
                            <div class="col-sm-6"><span class="text-muted d-block mb-1">Paid (verified)</span><strong style="color:var(--success);">PKR {{ frc_money($enrollment->paid_amount) }}</strong></div>
                            @if(($pendingSlipAmount ?? 0) > 0)
                            <div class="col-sm-6"><span class="text-muted d-block mb-1">Pending verification</span><strong style="color:#e08000;">{{ frc_pkr($pendingSlipAmount) }}</strong></div>
                            @endif
                            <div class="col-sm-6"><span class="text-muted d-block mb-1">You can pay now (max)</span><strong style="color:var(--danger);">{{ frc_pkr($uploadableAmount ?? $enrollment->outstandingForSlipUpload()) }}</strong></div>
                            <div class="col-sm-6"><span class="text-muted d-block mb-1">Branch</span><strong>{{ $enrollment->branch?->name }}</strong></div>
                        </div>
                    </div>

                    @if($frc['bank_name'] || $frc['bank_account_number'] || $frc['payment_instructions'])
                    <div class="alert-frc info mb-3" style="font-size:13px;">
                        <i class="fa-solid fa-building-columns"></i>
                        <div>
                            @if($frc['bank_name'] || $frc['bank_account_title'] || $frc['bank_account_number'])
                                <strong>Bank details</strong><br>
                                @if($frc['bank_name']){{ $frc['bank_name'] }}<br>@endif
                                @if($frc['bank_account_title']){{ $frc['bank_account_title'] }}<br>@endif
                                @if($frc['bank_account_number'])A/C: {{ $frc['bank_account_number'] }}@endif
                                <br>
                            @endif
                            @if($frc['payment_instructions'])<span class="text-muted">{{ $frc['payment_instructions'] }}</span>@endif
                        </div>
                    </div>
                    @endif

                    <form action="{{ route('child.upload-slip.store') }}" method="POST" enctype="multipart/form-data" class="form-frc" id="childSlipUploadForm" autocomplete="off">
                    @csrf
                    <input type="hidden" name="enrollment_id" value="{{ $enrollment->id }}">
                    <div class="mb-3">
                        <label>Amount (PKR) <span style="color:var(--danger)">*</span></label>
                        <input type="text" name="amount" value="{{ $repopulateForm ? old('amount') : '' }}"
                            class="form-control @error('amount') is-invalid @enderror"
                            inputmode="numeric" pattern="[0-9]*" maxlength="9"
                            max="{{ frc_money_input($uploadableAmount ?? $enrollment->outstandingForSlipUpload()) }}"
                            placeholder="Enter amount {{ frc_pkr($uploadableAmount ?? $enrollment->outstandingForSlipUpload()) }}" required autocomplete="off">
                        @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <small class="text-muted">Enter the <strong>exact whole PKR amount</strong> shown on your bank slip or transfer receipt.</small>
                    </div>
                    @php
                        $childPaymentMethods = ['bank_transfer', 'easypaisa', 'jazzcash', 'card', 'other'];
                    @endphp
                    <div class="mb-3">
                        <label>Payment Method <span style="color:var(--danger)">*</span></label>
                        <select name="payment_method" class="form-control @error('payment_method') is-invalid @enderror" required>
                            <option value="">Select payment method</option>
                            @foreach($childPaymentMethods as $m)
                                <option value="{{ $m }}" {{ $repopulateForm && old('payment_method') === $m ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $m)) }}</option>
                            @endforeach
                        </select>
                        @error('payment_method') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label>Transaction / Reference ID <span style="color:var(--text-muted);font-weight:normal;font-size:12px;">(optional)</span></label>
                        <input type="text" name="transaction_reference" value="{{ $repopulateForm ? old('transaction_reference') : '' }}"
                            class="form-control @error('transaction_reference') is-invalid @enderror"
                            placeholder="Bank ref, Easypaisa TID, etc.">
                        @error('transaction_reference') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label>Payment Slip <span style="color:var(--danger)">*</span></label>
                        <input type="file" name="payment_slip" accept=".jpg,.jpeg,.png,.pdf,.webp"
                            class="form-control @error('payment_slip') is-invalid @enderror">
                        <div style="font-size:11px;color:var(--text-muted);margin-top:4px;">Accepted: JPG, PNG, PDF, WebP — max 2MB</div>
                        @error('payment_slip') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label>Payment Date <span style="color:var(--danger)">*</span></label>
                        <input type="date" name="payment_date" value="{{ $repopulateForm ? old('payment_date', date('Y-m-d')) : date('Y-m-d') }}"
                            class="form-control @error('payment_date') is-invalid @enderror">
                        @error('payment_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label>Notes (optional)</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Any notes..." autocomplete="off">{{ $repopulateForm ? old('notes') : '' }}</textarea>
                    </div>
                    <button type="submit" class="btn-teal w-100 justify-content-center py-2">
                        <i class="fa-solid fa-upload"></i> Submit Payment Slip
                    </button>
                    </form>
                @elseif($enrollment && ($pendingSlipAmount ?? 0) > 0)
                    <div class="mb-3" style="font-size:13px;color:var(--navy);">
                        <strong>{{ $enrollment->service?->name ?? 'Programme' }}</strong>
                        <span class="text-muted">· Enrollment #{{ $enrollment->id }}</span>
                    </div>
                    <div style="background:#fff4e5;border-radius:14px;padding:18px 20px;border:1px solid #ffe0b2;">
                        <div style="display:flex;gap:12px;align-items:flex-start;">
                            <i class="fa-solid fa-hourglass-half" style="color:#c77800;font-size:22px;margin-top:2px;"></i>
                            <div>
                                <strong style="color:var(--navy);display:block;margin-bottom:6px;">Payment slip already submitted</strong>
                                <span style="font-size:13px;color:var(--text-muted);">
                                    {{ frc_pkr($pendingSlipAmount) }} is awaiting finance verification for this programme.
                                    You cannot upload another slip for the same balance until it is verified or rejected.
                                    Check <a href="{{ route('child.payments') }}" style="color:var(--teal);font-weight:600;">Payment History</a> for status.
                                </span>
                            </div>
                        </div>
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>
@push('scripts')
<script nonce="{{ $cspNonce }}">
(function () {
    function reloadIfBackForward() {
        const nav = performance.getEntriesByType('navigation')[0];
        if (nav && nav.type === 'back_forward') {
            window.location.replace(window.location.href);
            return true;
        }
        return false;
    }
    window.addEventListener('pageshow', function (event) {
        if (event.persisted && !reloadIfBackForward()) {
            window.location.replace(window.location.href);
        }
    });
    if (document.readyState === 'complete') {
        reloadIfBackForward();
    } else {
        window.addEventListener('load', reloadIfBackForward);
    }
})();
document.getElementById('childSlipUploadForm')?.addEventListener('submit', function (e) {
    const input = this.querySelector('input[name="amount"]');
    const amount = input?.value?.trim();
    if (!amount) {
        return;
    }
    const label = 'PKR ' + Number(amount).toLocaleString('en-PK');
    if (!confirm('You are submitting a payment of ' + label + ' Continue?')) {
        e.preventDefault();
    }
});
document.querySelector('input[name="amount"]')?.addEventListener('input', function () {
    this.value = String(this.value || '').replace(/\D/g, '');
});
</script>
@endpush
@endsection
