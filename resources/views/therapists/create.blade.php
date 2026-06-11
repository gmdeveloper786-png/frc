@extends('layouts.app')
@section('title', 'Add Therapist')
@section('page-title', 'Add Therapist')

@section('content')
<form action="{{ route('therapists.store') }}" method="POST" enctype="multipart/form-data" class="form-frc">
@csrf

{{-- Section 1: Personal Info --}}
<div class="form-section">
    <div class="form-section-title"><i class="fa-solid fa-user-doctor" style="color:var(--teal);"></i> Personal Information</div>
    <div class="row g-3">
        <div class="col-md-6">
            <label>Full Name <span style="color:var(--danger)">*</span></label>
            <input type="text" name="full_name" value="{{ old('full_name') }}" class="form-control @error('full_name') is-invalid @enderror" placeholder="Full name">
            @error('full_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-6">
            <label>Father's Name</label>
            <input type="text" name="father_name" value="{{ old('father_name') }}" class="form-control">
        </div>
        <div class="col-md-6">
            <label>Email <span style="color:var(--danger)">*</span></label>
            <input type="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror">
            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-6">
            <label>Gender</label>
            <select name="gender" class="form-control">
                <option value="">Select</option>
                <option value="male" {{ old('gender') == 'male' ? 'selected' : '' }}>Male</option>
                <option value="female" {{ old('gender') == 'female' ? 'selected' : '' }}>Female</option>
                <option value="other" {{ old('gender') == 'other' ? 'selected' : '' }}>Other</option>
            </select>
        </div>
        <div class="col-md-4">
            <label>Date of Birth</label>
            <input type="date" name="date_of_birth" value="{{ old('date_of_birth') }}"
                class="form-control @error('date_of_birth') is-invalid @enderror"
                max="{{ now()->format('Y-m-d') }}">
            @error('date_of_birth') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-4">
            <label>Phone Number</label>
            <input type="text" name="phone_number" value="{{ old('phone_number') }}" class="form-control">
        </div>
        <div class="col-md-4">
            <label>WhatsApp Number</label>
            <input type="text" name="whatsapp_number" value="{{ old('whatsapp_number') }}" class="form-control">
        </div>
        <div class="col-12">
            <label>Address</label>
            <textarea name="address" class="form-control" rows="2">{{ old('address') }}</textarea>
        </div>
        <div class="col-md-6">
            <label for="therapistPassword">Password <span style="color:var(--danger)">*</span></label>
            <div class="auth-pass-wrap">
                <input type="password" id="therapistPassword" name="password" minlength="8" class="form-control @error('password') is-invalid @enderror" placeholder="e.g. Therapist@123" autocomplete="new-password">
                <button type="button" class="auth-pass-toggle" data-pass-toggle="therapistPassword" data-pass-icon="therapistPassIcon" aria-label="Show password">
                    <i class="fa-regular fa-eye" id="therapistPassIcon"></i>
                </button>
            </div>
            @error('password') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            <div class="form-text">At least 8 characters, with letters and numbers.</div>
        </div>
        <div class="col-md-6">
            <label for="therapistPasswordConfirmation">Confirm Password <span style="color:var(--danger)">*</span></label>
            <div class="auth-pass-wrap">
                <input type="password" id="therapistPasswordConfirmation" name="password_confirmation" minlength="8" class="form-control @error('password') is-invalid @enderror" placeholder="Repeat password" autocomplete="new-password">
                <button type="button" class="auth-pass-toggle" data-pass-toggle="therapistPasswordConfirmation" data-pass-icon="therapistPassConfirmIcon" aria-label="Show password">
                    <i class="fa-regular fa-eye" id="therapistPassConfirmIcon"></i>
                </button>
            </div>
        </div>
   
    </div>
</div>

{{-- Section 2: Professional Info --}}
<div class="form-section">
    <div class="form-section-title"><i class="fa-solid fa-stethoscope" style="color:var(--teal);"></i> Professional Information</div>
    <div class="row g-3">
        <div class="col-md-6">
            <label>CNIC Number</label>
            <input type="text" name="cnic_number" value="{{ old('cnic_number') }}" class="form-control" placeholder="XXXXX-XXXXXXX-X">
        </div>
        <div class="col-md-6">
            <label>Qualification</label>
            <input type="text" name="qualification" value="{{ old('qualification') }}" class="form-control">
        </div>
        <div class="col-12">
            <label>Specialization / Services <span style="color:var(--danger)">*</span></label>
            @error('service_ids') <div class="invalid-feedback d-block mb-2">{{ $message }}</div> @enderror
            <div id="therapistServiceBadges" style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:10px;min-height:8px;"></div>
            <div style="display:flex;flex-wrap:wrap;gap:8px;">
                @foreach($services as $service)
                    <label style="display:flex;align-items:center;gap:8px;padding:8px 14px;border:1.5px solid var(--border-soft);border-radius:10px;cursor:pointer;transition:all .2s;" class="tservice-check">
                        <input type="checkbox" name="service_ids[]" value="{{ $service->id }}"
                            {{ in_array($service->id, old('service_ids', [])) ? 'checked' : '' }}
                            style="accent-color:var(--teal);">
                        <span style="font-size:13px;font-weight:500;">{{ $service->name }}</span>
                    </label>
                @endforeach
            </div>
        </div>
        <div class="col-md-6">
            <label>Branch <span style="color:var(--danger)">*</span></label>
            @if($branches->count() === 1)
                @php $onlyBranch = $branches->first(); @endphp
                <input type="hidden" name="branch_id" value="{{ $onlyBranch->id }}">
                <input type="text" class="form-control" value="{{ $onlyBranch->displayLabel() }}" readonly>
            @else
                <select name="branch_id" class="form-control @error('branch_id') is-invalid @enderror" required>
                    <option value="">Select Branch</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ (string) old('branch_id') === (string) $branch->id ? 'selected' : '' }}>{{ $branch->displayLabel() }}</option>
                    @endforeach
                </select>
            @endif
            @error('branch_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>
</div>

{{-- Section 3: Schedule --}}
<div class="form-section">
    <div class="form-section-title"><i class="fa-solid fa-calendar-days" style="color:var(--teal);"></i> Working Schedule</div>
    <div class="row g-3">
        <div class="col-12">
            <label>Working Days</label>
            <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:6px;">
                @foreach(['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'] as $day)
                    <label style="display:flex;align-items:center;gap:6px;background:var(--bg-light);border:1.5px solid var(--border-soft);padding:7px 14px;border-radius:8px;cursor:pointer;transition:all .2s;">
                        <input type="checkbox" name="working_days[]" value="{{ $day }}"
                            {{ in_array($day, old('working_days', [])) ? 'checked' : '' }}
                            style="accent-color:var(--teal);">
                        {{ $day }}
                    </label>
                @endforeach
            </div>
        </div>
        <div class="col-md-3">
            <label>Session Start Time <span style="color:var(--danger)">*</span></label>
            <input type="time" name="slot_start" value="{{ old('slot_start', '09:00') }}" class="form-control @error('slot_start') is-invalid @enderror">
            @error('slot_start') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-3">
            <label>Session End Time <span style="color:var(--danger)">*</span></label>
            <input type="time" name="slot_end" value="{{ old('slot_end', '17:00') }}" class="form-control @error('slot_end') is-invalid @enderror">
            @error('slot_end') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-3">
            <label>Break Start</label>
            <input type="time" name="break_start" value="{{ old('break_start', '13:00') }}" class="form-control">
        </div>
        <div class="col-md-3">
            <label>Break End</label>
            <input type="time" name="break_end" value="{{ old('break_end', '14:00') }}" class="form-control">
        </div>
        <div class="col-md-4">
            <label>Profile Status</label>
            <select name="profile_status" class="form-control">
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
        </div>
    </div>

    {{-- Slot Preview --}}
    <div id="slotPreview" style="margin-top:16px;display:none;">
        <label style="font-size:13px;font-weight:600;color:var(--navy);margin-bottom:8px;display:block;">Generated Slots Preview</label>
        <div id="slotsContainer" style="display:flex;flex-wrap:wrap;gap:8px;"></div>
    </div>
    <button type="button" class="btn-outline-teal mt-3" id="previewSlotsBtn">
        <i class="fa-solid fa-clock"></i> Preview Time Slots
    </button>
</div>

{{-- Section 4: Documents --}}
{{-- <div class="form-section">
    <div class="form-section-title"><i class="fa-solid fa-file-alt" style="color:var(--teal);"></i> Documents (Optional)</div>
    <div class="mb-3">
        <input type="file" name="documents[]" multiple class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp">
        <div class="form-text">Max 2MB per file. Allowed: PDF, JPG, PNG, WebP</div>
    </div>
</div> --}}

<div style="display:flex;gap:12px;">
    <button type="submit" class="btn-teal" style="padding:10px 28px;"><i class="fa-solid fa-check"></i> Create Therapist</button>
    <a href="{{ route('therapists.index') }}" class="btn-outline-teal">Cancel</a>
</div>
</form>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce }}">
function previewSlots() {
    const start = document.querySelector('[name="slot_start"]').value;
    const end   = document.querySelector('[name="slot_end"]').value;
    const bs    = document.querySelector('[name="break_start"]').value;
    const be    = document.querySelector('[name="break_end"]').value;

    if (!start || !end) { alert('Please set start and end time first.'); return; }

    const slots = generateSlots(start, end, bs, be, 30);
    const container = document.getElementById('slotsContainer');
    const preview   = document.getElementById('slotPreview');

    container.innerHTML = '';
    preview.style.display = 'block';

    slots.forEach(s => {
        const el = document.createElement('span');
        el.style.cssText = `padding:5px 12px;border-radius:8px;font-size:12px;border:1.5px solid ${s.disabled ? '#e0e0e0' : 'var(--teal)'};color:${s.disabled ? '#aaa' : 'var(--teal)'};background:${s.disabled ? '#f8f8f8' : 'var(--teal-light)'}`;
        el.textContent = s.label;
        if (s.disabled) el.title = 'Break time';
        container.appendChild(el);
    });
}

function generateSlots(startStr, endStr, breakStart, breakEnd, interval) {
    const slots = [];
    const toMin = t => { const [h, m] = t.split(':').map(Number); return h * 60 + m; };
    const toStr = m => {
        const h = Math.floor(m / 60), mn = m % 60;
        const ampm = h >= 12 ? 'PM' : 'AM';
        return `${((h % 12) || 12)}:${mn.toString().padStart(2, '0')}${ampm}`;
    };
    let cur = toMin(startStr), endMin = toMin(endStr);
    const bsMin = breakStart ? toMin(breakStart) : null;
    const beMin = breakEnd   ? toMin(breakEnd)   : null;

    while (cur + interval <= endMin) {
        const slotEnd = cur + interval;
        const disabled = bsMin !== null && cur >= bsMin && cur < beMin;
        slots.push({ label: `${toStr(cur)} - ${toStr(slotEnd)}`, disabled });
        cur = slotEnd;
    }
    return slots;
}
function highlightTherapistService(cb) {
    const label = cb.closest('label');
    if (cb.checked) { label.style.borderColor = 'var(--teal)'; label.style.background = 'var(--teal-light)'; }
    else { label.style.borderColor = 'var(--border-soft)'; label.style.background = ''; }
}
function syncTherapistServiceBadges() {
    const wrap = document.getElementById('therapistServiceBadges');
    if (!wrap) return;
    wrap.innerHTML = '';
    document.querySelectorAll('.tservice-check input:checked').forEach(cb => {
        const spanLabel = cb.closest('label')?.querySelector('span');
        const name = spanLabel ? spanLabel.textContent.trim() : '';
        if (!name) return;
        const chip = document.createElement('span');
        chip.style.cssText = 'background:var(--teal-light);color:var(--teal-dark);padding:4px 10px;border-radius:8px;font-size:12px;border:1px solid var(--border-soft);';
        chip.textContent = name;
        wrap.appendChild(chip);
    });
}
document.querySelectorAll('.tservice-check input').forEach(function (cb) {
    cb.addEventListener('change', function () {
        highlightTherapistService(cb);
        syncTherapistServiceBadges();
    });
});
document.querySelectorAll('.tservice-check input:checked').forEach(highlightTherapistService);
syncTherapistServiceBadges();
document.getElementById('previewSlotsBtn')?.addEventListener('click', previewSlots);
</script>
@endpush
