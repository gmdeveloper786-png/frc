@extends('layouts.app')
@section('title', 'Upload Payment Slip')
@section('page-title', 'Upload Payment Slip')

@section('content')
@php
    $eligibleList = isset($eligible) ? $eligible : collect();
@endphp
<div class="row g-3 justify-content-center">
    <div class="col-lg-7 col-md-9">
        <div class="card-frc">
            <h6 style="font-family:'Poppins',sans-serif;color:var(--navy);margin-bottom:16px;">
                <i class="fa-solid fa-upload me-2" style="color:var(--teal);"></i>Fee payment — {{ auth()->user()->full_name }}
            </h6>

            @if(! ($hasVisibleEnrollments ?? false))
                <div class="empty-state" style="padding:32px 20px;">
                    <i class="fa-solid fa-file-contract empty-icon"></i>
                    <h5>No enrollment</h5>
                    <p class="mb-0">You don’t have an approved enrollment yet. Please contact the administration.</p>
                </div>
            @elseif($eligibleList->isEmpty())
                <div style="background:var(--teal-light);border-radius:14px;padding:18px 20px;margin-bottom:20px;border:1px solid rgba(0,128,128,.15);">
                    <div style="display:flex;gap:12px;align-items:flex-start;">
                        <i class="fa-solid fa-circle-check" style="color:var(--teal);font-size:22px;margin-top:2px;"></i>
                        <div>
                            <strong style="color:var(--navy);display:block;margin-bottom:6px;">All programme fees are fully paid.</strong>
                            <span style="font-size:13px;color:var(--text-muted);">You do not need to upload a payment slip for any of your active programmes. If you have more than one enrollment, each is billed separately — when every one shows as fully paid, no slip is required.</span>
                        </div>
                    </div>
                </div>
            @else
                @if($eligibleList->count() > 1)
                    <form method="GET" action="{{ route('child.upload-slip') }}" class="form-frc mb-4" id="enrollmentPickForm">
                        <label class="form-label" style="font-weight:600;color:var(--navy);">Which programme are you paying for? <span class="text-danger">*</span></label>
                        <p class="text-muted small mb-2">Each enrollment has its own fee. Select the one this slip belongs to, then fill the form below.</p>
                        <select name="enrollment_id" class="form-control" onchange="document.getElementById('enrollmentPickForm').submit()">
                            @foreach($eligibleList as $opt)
                                <option value="{{ $opt->id }}" @selected($enrollment && (int) $enrollment->id === (int) $opt->id)>
                                    #{{ $opt->id }} — {{ $opt->service?->name ?? 'Programme' }} — remaining PKR {{ number_format((float) $opt->getRawOriginal('remaining_amount'), 2) }}
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
                            <div class="col-sm-6"><span class="text-muted d-block mb-1">Total fee</span><strong class="text-navy">PKR {{ number_format($enrollment->final_total, 2) }}</strong></div>
                            <div class="col-sm-6"><span class="text-muted d-block mb-1">Paid</span><strong style="color:var(--success);">PKR {{ number_format($enrollment->paid_amount, 2) }}</strong></div>
                            <div class="col-sm-6"><span class="text-muted d-block mb-1">Remaining (max you can pay)</span><strong style="color:var(--danger);">PKR {{ number_format($enrollment->outstandingAmount(), 2) }}</strong></div>
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

                    <form action="{{ route('child.upload-slip.store') }}" method="POST" enctype="multipart/form-data" class="form-frc">
                    @csrf
                    <input type="hidden" name="enrollment_id" value="{{ $enrollment->id }}">
                    <div class="mb-3">
                        <label>Amount (PKR) <span style="color:var(--danger)">*</span></label>
                        <input type="number" name="amount" value="{{ old('amount') }}"
                            class="form-control @error('amount') is-invalid @enderror"
                            min="0.01" step="0.01" max="{{ number_format($enrollment->outstandingAmount(), 2, '.', '') }}"
                            placeholder="Enter amount up to PKR {{ number_format($enrollment->outstandingAmount(), 2) }}">
                        @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <small class="text-muted">Must be greater than 0 and cannot exceed the <strong>remaining balance for this programme</strong> only.</small>
                    </div>
                    @php
                        $childPaymentMethods = ['bank_transfer', 'easypaisa', 'jazzcash', 'card', 'other'];
                    @endphp
                    <div class="mb-3">
                        <label>Payment Method <span style="color:var(--danger)">*</span></label>
                        <select name="payment_method" class="form-control @error('payment_method') is-invalid @enderror" required>
                            <option value="">Select payment method</option>
                            @foreach($childPaymentMethods as $m)
                                <option value="{{ $m }}" {{ old('payment_method') === $m ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $m)) }}</option>
                            @endforeach
                        </select>
                        @error('payment_method') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label>Transaction / Reference ID <span style="color:var(--text-muted);font-weight:normal;font-size:12px;">(optional)</span></label>
                        <input type="text" name="transaction_reference" value="{{ old('transaction_reference') }}"
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
                        <input type="date" name="payment_date" value="{{ old('payment_date', date('Y-m-d')) }}"
                            class="form-control @error('payment_date') is-invalid @enderror">
                        @error('payment_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label>Notes (optional)</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Any notes...">{{ old('notes') }}</textarea>
                    </div>
                    <button type="submit" class="btn-teal w-100 justify-content-center py-2">
                        <i class="fa-solid fa-upload"></i> Submit Payment Slip
                    </button>
                    </form>
                @endif
            @endif
        </div>
    </div>
</div>
@endsection
