@extends('layouts.app')
@section('title', 'Register Child')
@section('page-title', 'Register Child')

@section('content')
@php
    $selectedDisabilityIds = old('disability_ids', []);
    $lockedBranchModel = $lockedBranch ? $branches->firstWhere('id', $lockedBranch) : null;
@endphp

<div class="mb-3">
    <a href="{{ route('children.index') }}" class="btn-outline-teal" style="font-size:13px;"><i class="fa-solid fa-arrow-left"></i> Back to children</a>
</div>

<div class="alert-frc alert-frc--staff-register-info">
    <i class="fa-solid fa-circle-info"></i>
    <div>
        Staff registration — the child account is <strong>approved immediately</strong> and can log in right away. No approval email is sent.
    </div>
</div>

<form action="{{ route('children.store') }}" method="POST" class="form-frc" enctype="multipart/form-data">
@csrf

<div class="form-section">
    <div class="form-section-title"><i class="fa-solid fa-child" style="color:var(--teal);"></i> Child Information</div>
    <div class="row g-3">
        <div class="col-md-6">
            <label>Full Name <span style="color:var(--danger)">*</span></label>
            <input type="text" name="full_name" value="{{ old('full_name') }}" class="form-control @error('full_name') is-invalid @enderror" placeholder="Child's full name">
            @error('full_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-6">
            <label>Father's Name</label>
            <input type="text" name="father_name" value="{{ old('father_name') }}" class="form-control @error('father_name') is-invalid @enderror" placeholder="Father's name">
            @error('father_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-6">
            <label>Email <span style="color:var(--danger)">*</span></label>
            <input type="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" placeholder="email@example.com" autocomplete="off">
            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-6">
            <label>FRC Branch <span style="color:var(--danger)">*</span></label>
            @if($lockedBranch && $lockedBranchModel)
                <input type="text" value="{{ $lockedBranchModel->displayLabel() }}" class="form-control" readonly tabindex="-1" style="background:var(--bg-soft,#f4f7fa);">
                <input type="hidden" name="branch_id" value="{{ $lockedBranch }}">
            @else
                <select name="branch_id" class="form-control @error('branch_id') is-invalid @enderror">
                    <option value="">Select branch</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ (string) old('branch_id') === (string) $branch->id ? 'selected' : '' }}>
                            {{ $branch->displayLabel() }}
                        </option>
                    @endforeach
                </select>
            @endif
            @error('branch_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-4">
            <label>Date of Birth</label>
            <input type="date" name="date_of_birth" id="date_of_birth" value="{{ old('date_of_birth') }}"
                class="form-control @error('date_of_birth') is-invalid @enderror" max="{{ date('Y-m-d') }}">
            @error('date_of_birth') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-4">
            <label>Age</label>
            <input type="number" name="age" id="age" value="{{ old('age') }}" class="form-control @error('age') is-invalid @enderror" min="0" max="120" readonly>
            @error('age') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-4">
            <label>Gender</label>
            <select name="gender" class="form-control @error('gender') is-invalid @enderror">
                <option value="">Select</option>
                <option value="male" {{ old('gender') === 'male' ? 'selected' : '' }}>Male</option>
                <option value="female" {{ old('gender') === 'female' ? 'selected' : '' }}>Female</option>
                <option value="other" {{ old('gender') === 'other' ? 'selected' : '' }}>Other</option>
            </select>
            @error('gender') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-6">
            <label>Phone</label>
            <input type="text" name="phone_number" value="{{ old('phone_number') }}" class="form-control @error('phone_number') is-invalid @enderror" placeholder="e.g. 03121234567">
            @error('phone_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-6">
            <label>WhatsApp</label>
            <input type="text" name="whatsapp_number" value="{{ old('whatsapp_number') }}" class="form-control @error('whatsapp_number') is-invalid @enderror" placeholder="e.g. 03121234567">
            @error('whatsapp_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-12">
            <label>Address</label>
            <textarea name="address" class="form-control @error('address') is-invalid @enderror" rows="2" placeholder="Home address">{{ old('address') }}</textarea>
            @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-12">
            <label>Parent Notes</label>
            <textarea name="parent_notes" class="form-control @error('parent_notes') is-invalid @enderror" rows="3" placeholder="Any notes for administration...">{{ old('parent_notes') }}</textarea>
            @error('parent_notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>
</div>

<div class="form-section">
    <div class="form-section-title"><i class="fa-solid fa-heart-pulse" style="color:var(--teal);"></i> Present Complaints</div>
    <p style="font-size:13px;color:var(--text-muted);margin-bottom:12px;">Select all that apply.</p>
    <div class="row g-2">
        @foreach($disabilities as $disability)
            <div class="col-md-6">
                <label style="display:flex;align-items:center;gap:10px;padding:10px 14px;border:1.5px solid var(--border-soft);border-radius:10px;cursor:pointer;">
                    <input type="checkbox" name="disability_ids[]" value="{{ $disability->id }}"
                        {{ in_array($disability->id, $selectedDisabilityIds, true) ? 'checked' : '' }}
                        style="width:16px;height:16px;accent-color:var(--teal);flex-shrink:0;">
                    <span style="font-size:14px;">{{ $disability->name }}</span>
                </label>
            </div>
        @endforeach
    </div>
    @error('disability_ids') <div class="text-danger small mt-2">{{ $message }}</div> @enderror
    <div id="createOtherDisabilityField" class="mt-3" style="display:none;">
        <label>Please describe the present complaint</label>
        <textarea name="other_disability" class="form-control @error('other_disability') is-invalid @enderror" rows="2" placeholder="Describe the present complaint...">{{ old('other_disability') }}</textarea>
        @error('other_disability') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
    </div>
</div>

<div class="form-section">
    <div class="form-section-title"><i class="fa-solid fa-file-alt" style="color:var(--teal);"></i> Documents</div>
    <p class="frc-documents__intro">Upload registration or supporting documents (optional). Only visible to administration.</p>
    @include('partials.child-documents', [
        'documents' => [],
        'editable' => true,
    ])
</div>

<div class="form-section">
    <div class="form-section-title"><i class="fa-solid fa-key" style="color:var(--teal);"></i> Login Password</div>
    <p style="font-size:13px;color:var(--text-muted);margin:-6px 0 16px;">Set the initial password the child will use to sign in.</p>
    <div class="row g-3">
        <div class="col-md-6">
            <label for="childCreatePassword">Password <span style="color:var(--danger)">*</span></label>
            <div class="auth-pass-wrap">
                <input type="password" id="childCreatePassword" name="password" class="form-control @error('password') is-invalid @enderror" autocomplete="new-password">
                <button type="button" class="auth-pass-toggle" data-pass-toggle="childCreatePassword" data-pass-icon="childCreatePassIcon" aria-label="Show password">
                    <i class="fa-regular fa-eye" id="childCreatePassIcon"></i>
                </button>
            </div>
            @error('password') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            <div class="form-text">At least 8 characters, with letters and numbers.</div>
        </div>
        <div class="col-md-6">
            <label for="childCreatePasswordConfirmation">Confirm Password <span style="color:var(--danger)">*</span></label>
            <div class="auth-pass-wrap">
                <input type="password" id="childCreatePasswordConfirmation" name="password_confirmation" class="form-control" autocomplete="new-password">
                <button type="button" class="auth-pass-toggle" data-pass-toggle="childCreatePasswordConfirmation" data-pass-icon="childCreatePassConfirmIcon" aria-label="Show password">
                    <i class="fa-regular fa-eye" id="childCreatePassConfirmIcon"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<div style="display:flex;gap:12px;flex-wrap:wrap;">
    <button type="submit" class="btn-teal"><i class="fa-solid fa-user-plus"></i> Register &amp; Approve</button>
    <a href="{{ route('children.index') }}" class="btn-outline-teal">Cancel</a>
</div>
</form>

@include('partials.child-documents-scripts')

@push('scripts')
<script nonce="{{ $cspNonce }}">
document.addEventListener('DOMContentLoaded', function () {
    function syncAgeFromDateOfBirth() {
        const dobInput = document.querySelector('[name="date_of_birth"]');
        const ageInput = document.querySelector('[name="age"]');
        if (!dobInput || !ageInput) return;

        const dateStr = dobInput.value;
        if (!dateStr) {
            ageInput.value = '';
            return;
        }

        const dob = new Date(dateStr + 'T12:00:00');
        const today = new Date();
        today.setHours(12, 0, 0, 0);

        let years = today.getFullYear() - dob.getFullYear();
        const monthDiff = today.getMonth() - dob.getMonth();
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dob.getDate())) {
            years--;
        }

        ageInput.value = years >= 0 && years <= 120 ? years : '';
    }

    const dobInput = document.querySelector('[name="date_of_birth"]');
    if (dobInput) {
        dobInput.addEventListener('change', syncAgeFromDateOfBirth);
        dobInput.addEventListener('input', syncAgeFromDateOfBirth);
        syncAgeFromDateOfBirth();
    }

    const otherField = document.getElementById('createOtherDisabilityField');
    const boxes = document.querySelectorAll('input[name="disability_ids[]"]');
    if (!otherField || !boxes.length) return;

    function syncOtherField() {
        const otherChecked = Array.from(boxes).some(cb =>
            cb.checked && cb.closest('label')?.querySelector('span')?.textContent.trim() === 'Other'
        );
        otherField.style.display = otherChecked ? 'block' : 'none';
    }

    boxes.forEach(cb => cb.addEventListener('change', syncOtherField));
    syncOtherField();
});
</script>
@endpush
@endsection
