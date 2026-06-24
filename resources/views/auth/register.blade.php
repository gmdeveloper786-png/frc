@extends('layouts.auth')
@section('title', 'Register — Faizan Rehabilitation Centre')

@push('styles')
<style>
.step-panel { display: none; }
.step-panel.active { display: block; }
</style>
@endpush

@section('content')
<div class="auth-card auth-card-register">
    <div class="auth-register-header">
        <div class="auth-register-logo">
            <img src="{{ asset('images/logo.png') }}" alt="{{ $frc['organisation_name'] ?? 'Faizan Rehabilitation Centre' }}">
        </div>
        <div class="auth-register-header-text">
            <h4>Child Registration</h4>
            <p>{{ $frc['organisation_name'] ?? 'Faizan Rehabilitation Centre' }}</p>
        </div>
    </div>

    <div class="auth-register-body">
        {{-- Steps Progress --}}
        <div class="steps-progress">
            <div class="step-item">
                <div class="step-circle active" id="step-circle-1">1</div>
            </div>
            <div class="step-line" id="step-line-1"></div>
            <div class="step-item">
                <div class="step-circle" id="step-circle-2">2</div>
            </div>
            <div class="step-line" id="step-line-2"></div>
            <div class="step-item">
                <div class="step-circle" id="step-circle-3">3</div>
            </div>
            <div class="step-line" id="step-line-3"></div>
            <div class="step-item">
                <div class="step-circle" id="step-circle-4">4</div>
            </div>
        </div>

        @if($errors->any())
            <div class="alert-frc error" style="margin-bottom:20px;">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <div>
                    @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('register.post') }}" class="form-frc form-frc-register" id="registerForm" novalidate>
            @csrf

            {{-- Step 1: Personal Info --}}
            <div class="step-panel active" id="panel-1">
                <h5 class="step-title">
                    <i class="fa-solid fa-user-circle"></i> Personal Information
                </h5>
                <div class="form-grid">
                    <div>
                        <label>Full Name <span style="color:var(--danger)">*</span></label>
                        <input type="text" name="full_name" value="{{ old('full_name') }}"
                            class="form-control @error('full_name') is-invalid @enderror"
                            placeholder="Child's full name" required>
                        @error('full_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label>Father's Name</label>
                        <input type="text" name="father_name" value="{{ old('father_name') }}"
                            class="form-control" placeholder="Father's name">
                    </div>
                    <div>
                        <label>Email Address <span style="color:var(--danger)">*</span></label>
                        <input type="email" name="email" value="{{ old('email') }}"
                            class="form-control @error('email') is-invalid @enderror"
                            placeholder="email@example.com" required>
                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label>Gender</label>
                        <select name="gender" class="form-control">
                            <option value="">Select Gender</option>
                            <option value="male" {{ old('gender') === 'male' ? 'selected' : '' }}>Male</option>
                            <option value="female" {{ old('gender') === 'female' ? 'selected' : '' }}>Female</option>
                            <option value="other" {{ old('gender') === 'other' ? 'selected' : '' }}>Other</option>
                        </select>
                    </div>
                    <div>
                        <label>Date of Birth</label>
                        <input type="date" name="date_of_birth" id="date_of_birth" value="{{ old('date_of_birth') }}"
                            class="form-control" max="{{ date('Y-m-d') }}">
                    </div>
                    <div>
                        <label>Age</label>
                        <input type="number" name="age" id="age" value="{{ old('age') }}"
                            class="form-control" placeholder="Age in years" min="0" max="120" readonly>
                    </div>
                    <div>
                        <label for="password">Password <span style="color:var(--danger)">*</span></label>
                        <div class="auth-pass-wrap">
                            <input type="password" name="password" id="password"
                                class="form-control @error('password') is-invalid @enderror"
                                placeholder="e.g. Child@123" required minlength="8" autocomplete="new-password">
                            <button type="button" class="auth-pass-toggle" data-pass-toggle="password" data-pass-icon="regPassIcon" aria-label="Show password">
                                <i class="fa-regular fa-eye" id="regPassIcon"></i>
                            </button>
                        </div>
                        @error('password') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        <div class="form-text">At least 8 characters, with letters and numbers.</div>
                    </div>
                    <div>
                        <label for="password_confirmation">Confirm Password <span style="color:var(--danger)">*</span></label>
                        <div class="auth-pass-wrap">
                            <input type="password" name="password_confirmation" id="password_confirmation"
                                class="form-control @error('password') is-invalid @enderror"
                                placeholder="Repeat password" required minlength="8" autocomplete="new-password">
                            <button type="button" class="auth-pass-toggle" data-pass-toggle="password_confirmation" data-pass-icon="regPassConfirmIcon" aria-label="Show password">
                                <i class="fa-regular fa-eye" id="regPassConfirmIcon"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="step-actions step-actions--end">
                    <button type="button" class="btn-teal" data-reg-step="2">
                        Next <i class="fa-solid fa-arrow-right" style="margin-left:6px;"></i>
                    </button>
                </div>
            </div>

            {{-- Step 2: Contact Info --}}
            <div class="step-panel" id="panel-2">
                <h5 class="step-title">
                    <i class="fa-solid fa-address-card"></i> Branch & Contact Information
                </h5>
                <div class="form-grid">
                    <div class="col-full">
                        <label>FRC Branch <span style="color:var(--danger)">*</span></label>
                        <select name="branch_id" class="form-control @error('branch_id') is-invalid @enderror" required>
                            <option value="">Select the branch where therapy will take place</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ (string) old('branch_id') === (string) $branch->id ? 'selected' : '' }}>
                                    {{ $branch->displayLabel() }}
                                </option>
                            @endforeach
                        </select>
                        @error('branch_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <small style="color:var(--text-muted);font-size:12px;display:block;margin-top:6px;">
                            Your registration will be sent to the admin of this branch for approval.
                        </small>
                    </div>
                    <div>
                        <label>Phone Number</label>
                        <input type="text" name="phone_number" value="{{ old('phone_number') }}"
                            class="form-control" placeholder="03XX-XXXXXXX">
                    </div>
                    <div>
                        <label>WhatsApp Number</label>
                        <input type="text" name="whatsapp_number" value="{{ old('whatsapp_number') }}"
                            class="form-control" placeholder="03XX-XXXXXXX">
                    </div>
                    <div class="col-full">
                        <label>Address</label>
                        <textarea name="address" class="form-control" rows="3" placeholder="Home address">{{ old('address') }}</textarea>
                    </div>
                </div>
                <div class="step-actions">
                    <button type="button" class="btn-outline-teal" data-reg-step="1">
                        <i class="fa-solid fa-arrow-left" style="margin-right:6px;"></i> Back
                    </button>
                    <button type="button" class="btn-teal" data-reg-step="3">
                        Next <i class="fa-solid fa-arrow-right" style="margin-left:6px;"></i>
                    </button>
                </div>
            </div>

            {{-- Step 3: Present Complaint Info --}}
            <div class="step-panel" id="panel-3">
                <h5 class="step-title">
                    <i class="fa-solid fa-heart-pulse"></i> Present Complaint Information
                </h5>
                <p class="step-hint">Select all present complaints that apply to the child. You can select multiple.</p>
                <div class="form-grid form-grid--choices">
                    @foreach($disabilities as $disability)
                        <div>
                            <label style="display:flex;align-items:center;gap:10px;padding:10px 14px;border:1.5px solid var(--border-soft);border-radius:10px;cursor:pointer;transition:all .2s;margin-bottom:0;" class="disability-option">
                                <input type="checkbox" name="disability_ids[]" value="{{ $disability->id }}"
                                    {{ in_array($disability->id, old('disability_ids', [])) ? 'checked' : '' }}
                                    style="width:16px;height:16px;accent-color:var(--teal);flex-shrink:0;"
                                    >
                                <span style="font-size:14px;">{{ $disability->name }}</span>
                            </label>
                        </div>
                    @endforeach
                </div>

                <div id="otherDisabilityField" style="display:none;margin-top:20px;">
                    <label>Please describe the present complaint</label>
                    <textarea name="other_disability" class="form-control" rows="2" placeholder="Describe the present complaint...">{{ old('other_disability') }}</textarea>
                </div>

                <div class="step-actions">
                    <button type="button" class="btn-outline-teal" data-reg-step="2">
                        <i class="fa-solid fa-arrow-left" style="margin-right:6px;"></i> Back
                    </button>
                    <button type="button" class="btn-teal" data-reg-step="4">
                        Next <i class="fa-solid fa-arrow-right" style="margin-left:6px;"></i>
                    </button>
                </div>
            </div>

            {{-- Step 4: Notes & Submit --}}
            <div class="step-panel" id="panel-4">
                <h5 class="step-title">
                    <i class="fa-solid fa-notes-medical"></i> Notes & Submit
                </h5>
                <div class="form-grid">
                    <div class="col-full">
                        <label>Parent Notes / Additional Information</label>
                        <textarea name="parent_notes" class="form-control" rows="4"
                            placeholder="Any additional information the admin should know about the child...">{{ old('parent_notes') }}</textarea>
                    </div>
                </div>

                <div class="register-info-box">
                    <i class="fa-solid fa-circle-info"></i>
                    <div>
                        <strong>Important:</strong> After submitting your registration, your account will be <strong>pending admin approval</strong>. You will be able to login once an admin reviews and approves your registration. An email will be sent to you once your account is approved.
                    </div>
                </div>

                <div class="step-actions">
                    <button type="button" class="btn-outline-teal" data-reg-step="3">
                        <i class="fa-solid fa-arrow-left" style="margin-right:6px;"></i> Back
                    </button>
                    <button type="submit" class="btn-teal" style="padding:10px 28px;">
                        <i class="fa-solid fa-paper-plane" style="margin-right:6px;"></i> Submit Registration
                    </button>
                </div>
            </div>

        </form>

        <div class="auth-register-footer">
            Already have an account? <a href="{{ route('login') }}">Sign In</a>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce }}">
let currentStep = 1;
const registerForm = document.getElementById('registerForm');

@if($errors->any())
currentStep = {{ old('_step', 1) }};
@endif

function syncAgeFromDateOfBirth() {
    const dobInput = registerForm.querySelector('[name="date_of_birth"]');
    const ageInput = registerForm.querySelector('[name="age"]');
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

function setFieldError(input, message) {
    if (!input) return;
    input.classList.add('is-invalid');
    let feedback = input.parentElement.querySelector('.invalid-feedback.js-error');
    if (!feedback) {
        feedback = document.createElement('div');
        feedback.className = 'invalid-feedback js-error';
        input.parentElement.appendChild(feedback);
    }
    feedback.textContent = message;
}

function clearFieldError(input) {
    if (!input) return;
    input.classList.remove('is-invalid');
    const feedback = input.parentElement.querySelector('.invalid-feedback.js-error');
    if (feedback) feedback.remove();
    const panel = input.closest('.step-panel');
    if (panel) panel.querySelector('.step-error-summary')?.remove();
}

function clearStepErrors(step) {
    const panel = document.getElementById('panel-' + step);
    if (!panel) return;
    panel.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
    panel.querySelectorAll('.invalid-feedback.js-error').forEach(el => el.remove());
    const summary = panel.querySelector('.step-error-summary');
    if (summary) summary.remove();
}

function showStepErrorSummary(step, message) {
    const panel = document.getElementById('panel-' + step);
    let summary = panel.querySelector('.step-error-summary');
    if (!summary) {
        summary = document.createElement('div');
        summary.className = 'alert-frc error step-error-summary';
        summary.style.marginBottom = '16px';
    }
    const title = panel.querySelector('.step-title');
    if (title) title.after(summary);
    else panel.prepend(summary);
    summary.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i><div>' + message + '</div>';
}

function validateStep1() {
    clearStepErrors(1);
    let valid = true;
    let firstInvalid = null;

    const fullName = registerForm.querySelector('[name="full_name"]');
    const email = registerForm.querySelector('[name="email"]');
    const password = registerForm.querySelector('[name="password"]');
    const passwordConfirmation = registerForm.querySelector('[name="password_confirmation"]');
    const age = registerForm.querySelector('[name="age"]');
    const dateOfBirth = registerForm.querySelector('[name="date_of_birth"]');

    const name = fullName.value.trim();
    if (!name) {
        setFieldError(fullName, 'Full name is required.');
        firstInvalid = firstInvalid || fullName;
        valid = false;
    } else if (name.length > 255) {
        setFieldError(fullName, 'Full name may not exceed 255 characters.');
        firstInvalid = firstInvalid || fullName;
        valid = false;
    }

    const emailVal = email.value.trim();
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailVal) {
        setFieldError(email, 'Email address is required.');
        firstInvalid = firstInvalid || email;
        valid = false;
    } else if (!emailPattern.test(emailVal)) {
        setFieldError(email, 'Please enter a valid email address.');
        firstInvalid = firstInvalid || email;
        valid = false;
    }

    const pass = password.value;
    if (!pass) {
        setFieldError(password, 'Password is required.');
        firstInvalid = firstInvalid || password;
        valid = false;
    } else if (pass.length < 8) {
        setFieldError(password, 'Password must be at least 8 characters.');
        firstInvalid = firstInvalid || password;
        valid = false;
    } else if (!/[A-Za-z]/.test(pass)) {
        setFieldError(password, 'Password must contain at least one letter.');
        firstInvalid = firstInvalid || password;
        valid = false;
    } else if (!/[0-9]/.test(pass)) {
        setFieldError(password, 'Password must contain at least one number.');
        firstInvalid = firstInvalid || password;
        valid = false;
    }

    const passConfirm = passwordConfirmation.value;
    if (!passConfirm) {
        setFieldError(passwordConfirmation, 'Please confirm your password.');
        firstInvalid = firstInvalid || passwordConfirmation;
        valid = false;
    } else if (pass && pass !== passConfirm) {
        setFieldError(passwordConfirmation, 'Passwords do not match.');
        firstInvalid = firstInvalid || passwordConfirmation;
        valid = false;
    }

    if (age.value !== '' && (Number(age.value) < 0 || Number(age.value) > 120)) {
        setFieldError(age, 'Age must be between 0 and 120.');
        firstInvalid = firstInvalid || age;
        valid = false;
    }

    if (dateOfBirth.value && dateOfBirth.value >= new Date().toISOString().split('T')[0]) {
        setFieldError(dateOfBirth, 'Date of birth must be in the past.');
        firstInvalid = firstInvalid || dateOfBirth;
        valid = false;
    }

    if (!valid) {
        showStepErrorSummary(1, 'Please fix the errors below before continuing.');
        firstInvalid?.focus();
    }

    return valid;
}

function validateStep3() {
    clearStepErrors(3);
    const otherChecked = Array.from(document.querySelectorAll('input[name="disability_ids[]"]'))
        .some(cb => cb.checked && cb.closest('label').querySelector('span').textContent.trim() === 'Other');

    if (!otherChecked) return true;

    const otherField = registerForm.querySelector('[name="other_disability"]');
    if (!otherField.value.trim()) {
        setFieldError(otherField, 'Please describe the present complaint when "Other" is selected.');
        showStepErrorSummary(3, 'Please describe the selected "Other" present complaint.');
        otherField.focus();
        return false;
    }

    return true;
}

function validateStep2() {
    clearStepErrors(2);
    const branch = registerForm.querySelector('[name="branch_id"]');
    if (!branch.value) {
        setFieldError(branch, 'Please select a branch.');
        showStepErrorSummary(2, 'Please select the branch where you want to register.');
        branch.focus();
        return false;
    }
    return true;
}

function validateStep(step) {
    if (step === 1) return validateStep1();
    if (step === 2) return validateStep2();
    if (step === 3) return validateStep3();
    return true;
}

function validateAllSteps() {
    for (let s = 1; s <= 3; s++) {
        if (!validateStep(s)) {
            nextStep(s, true);
            return false;
        }
    }
    return true;
}

function nextStep(step, skipValidation = false) {
    if (!skipValidation && step > currentStep) {
        for (let s = currentStep; s < step; s++) {
            if (!validateStep(s)) {
                if (s !== currentStep) nextStep(s, true);
                return;
            }
        }
    }

    document.getElementById('panel-' + currentStep).classList.remove('active');
    document.getElementById('panel-' + step).classList.add('active');

    for (let i = 1; i <= 4; i++) {
        const c = document.getElementById('step-circle-' + i);
        if (i < step) {
            c.classList.remove('active');
            c.classList.add('done');
            c.innerHTML = '<i class="fa-solid fa-check" style="font-size:11px;"></i>';
        } else if (i === step) {
            c.classList.add('active');
            c.classList.remove('done');
            c.innerHTML = i;
        } else {
            c.classList.remove('active', 'done');
            c.innerHTML = i;
        }
    }
    for (let i = 1; i <= 3; i++) {
        const l = document.getElementById('step-line-' + i);
        if (i < step) l.classList.add('done');
        else l.classList.remove('done');
    }
    currentStep = step;
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function highlightOption(checkbox) {
    const label = checkbox.closest('label');
    if (checkbox.checked) {
        label.style.borderColor = 'var(--teal)';
        label.style.background = 'var(--teal-light)';
    } else {
        label.style.borderColor = 'var(--border-soft)';
        label.style.background = '';
    }

    const otherChecked = Array.from(document.querySelectorAll('input[name="disability_ids[]"]'))
        .some(cb => cb.checked && cb.closest('label').querySelector('span').textContent.trim() === 'Other');
    document.getElementById('otherDisabilityField').style.display = otherChecked ? 'block' : 'none';
}

document.querySelectorAll('[data-reg-step]').forEach(function (btn) {
    btn.addEventListener('click', function () {
        nextStep(parseInt(btn.getAttribute('data-reg-step'), 10));
    });
});

document.querySelectorAll('input[name="disability_ids[]"]').forEach(function (cb) {
    cb.addEventListener('change', function () { highlightOption(cb); });
});

document.querySelectorAll('input[name="disability_ids[]"]:checked').forEach(cb => highlightOption(cb));

registerForm.addEventListener('submit', function(e) {
    if (!validateAllSteps()) {
        e.preventDefault();
        return;
    }

    let stepInput = this.querySelector('input[name="_step"]');
    if (!stepInput) {
        stepInput = document.createElement('input');
        stepInput.type = 'hidden';
        stepInput.name = '_step';
        this.appendChild(stepInput);
    }
    stepInput.value = currentStep;
});

registerForm.querySelectorAll('input, select, textarea').forEach(el => {
    el.addEventListener('input', () => clearFieldError(el));
    el.addEventListener('change', () => clearFieldError(el));
});

const dateOfBirthInput = registerForm.querySelector('[name="date_of_birth"]');
if (dateOfBirthInput) {
    dateOfBirthInput.addEventListener('change', syncAgeFromDateOfBirth);
    dateOfBirthInput.addEventListener('input', syncAgeFromDateOfBirth);
    syncAgeFromDateOfBirth();
}

@if($errors->any())
document.addEventListener('DOMContentLoaded', () => nextStep(currentStep, true));
@endif
</script>
@endpush
