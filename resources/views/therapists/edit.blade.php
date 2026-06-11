@extends('layouts.app')
@section('title', 'Edit Therapist')
@section('page-title', 'Edit Therapist')

@section('content')
<form action="{{ route('therapists.update', $therapist->id) }}" method="POST" enctype="multipart/form-data" class="form-frc">
@csrf @method('PUT')
<div class="form-section">
    <div class="form-section-title"><i class="fa-solid fa-user-doctor" style="color:var(--teal);"></i> Personal Information</div>
    <div class="row g-3">
        <div class="col-md-6">
            <label>Full Name <span style="color:var(--danger)">*</span></label>
            <input type="text" name="full_name" value="{{ old('full_name', $therapist->full_name) }}" class="form-control @error('full_name') is-invalid @enderror">
            @error('full_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-6">
            <label>Email <span style="color:var(--danger)">*</span></label>
            <input type="email" name="email" value="{{ old('email', $therapist->email) }}" class="form-control @error('email') is-invalid @enderror">
            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-6">
            <label for="therapistEditPassword">New Password <span style="color:var(--text-muted);font-weight:400;">(leave blank to keep current)</span></label>
            <div class="auth-pass-wrap">
                <input type="password" id="therapistEditPassword" name="password" class="form-control @error('password') is-invalid @enderror" autocomplete="new-password">
                <button type="button" class="auth-pass-toggle" data-pass-toggle="therapistEditPassword" data-pass-icon="therapistEditPassIcon" aria-label="Show password">
                    <i class="fa-regular fa-eye" id="therapistEditPassIcon"></i>
                </button>
            </div>
            @error('password') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            <div class="form-text">If changing: at least 8 characters, with letters and numbers.</div>
        </div>
        <div class="col-md-6">
            <label for="therapistEditPasswordConfirmation">Confirm Password</label>
            <div class="auth-pass-wrap">
                <input type="password" id="therapistEditPasswordConfirmation" name="password_confirmation" class="form-control @error('password') is-invalid @enderror" autocomplete="new-password">
                <button type="button" class="auth-pass-toggle" data-pass-toggle="therapistEditPasswordConfirmation" data-pass-icon="therapistEditPassConfirmIcon" aria-label="Show password">
                    <i class="fa-regular fa-eye" id="therapistEditPassConfirmIcon"></i>
                </button>
            </div>
        </div>
        <div class="col-md-4">
            <label>Phone</label>
            <input type="text" name="phone_number" value="{{ old('phone_number', $therapist->phone_number) }}" class="form-control">
        </div>
        <div class="col-md-4">
            <label>WhatsApp</label>
            <input type="text" name="whatsapp_number" value="{{ old('whatsapp_number', $therapist->whatsapp_number) }}" class="form-control">
        </div>
        <div class="col-md-4">
            <label>Gender</label>
            <select name="gender" class="form-control">
                <option value="">Select</option>
                <option value="male" {{ old('gender', $therapist->gender) == 'male' ? 'selected' : '' }}>Male</option>
                <option value="female" {{ old('gender', $therapist->gender) == 'female' ? 'selected' : '' }}>Female</option>
            </select>
        </div>
        <div class="col-12">
            <label>Address</label>
            <textarea name="address" class="form-control" rows="2">{{ old('address', $therapist->address) }}</textarea>
        </div>
    </div>
</div>
<div class="form-section">
    <div class="form-section-title"><i class="fa-solid fa-stethoscope" style="color:var(--teal);"></i> Professional Info</div>
    <div class="row g-3">
        <div class="col-md-6">
            <label>Branch <span style="color:var(--danger)">*</span></label>
            @if($branches->count() === 1)
                @php $onlyBranch = $branches->first(); @endphp
                <input type="hidden" name="branch_id" value="{{ $onlyBranch->id }}">
                <input type="text" class="form-control" value="{{ $onlyBranch->displayLabel() }}" readonly>
            @else
                <select name="branch_id" class="form-control @error('branch_id') is-invalid @enderror" required>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ (string) old('branch_id', $therapist->therapistProfile?->branch_id) === (string) $branch->id ? 'selected' : '' }}>{{ $branch->displayLabel() }}</option>
                    @endforeach
                </select>
            @endif
            @error('branch_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
                <div class="col-md-6">
            <label>Qualification</label>
            <input type="text" name="qualification" value="{{ old('qualification', $therapist->therapistProfile?->qualification) }}" class="form-control">
        </div>
        @php $selectedServiceIds = old('service_ids', $therapist->therapistServices->pluck('id')->toArray()); @endphp
        <div class="col-12">
            <label>Specialization / Services <span style="color:var(--danger)">*</span></label>
            @error('service_ids') <div class="invalid-feedback d-block mb-2">{{ $message }}</div> @enderror
            <div id="therapistServiceBadges" style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:10px;min-height:8px;"></div>
            <div style="display:flex;flex-wrap:wrap;gap:8px;">
                @foreach($services as $service)
                    <label style="display:flex;align-items:center;gap:8px;padding:8px 14px;border:1.5px solid {{ in_array($service->id, $selectedServiceIds) ? 'var(--teal)' : 'var(--border-soft)' }};background:{{ in_array($service->id, $selectedServiceIds) ? 'var(--teal-light)' : '' }};border-radius:10px;cursor:pointer;transition:all .2s;" class="tservice-check">
                        <input type="checkbox" name="service_ids[]" value="{{ $service->id }}"
                            {{ in_array($service->id, $selectedServiceIds) ? 'checked' : '' }}
                            style="accent-color:var(--teal);">
                        <span style="font-size:13px;font-weight:500;">{{ $service->name }}</span>
                    </label>
                @endforeach
            </div>
        </div>
        <div class="col-md-6">
            <label>CNIC</label>
            <input type="text" name="cnic_number" value="{{ old('cnic_number', $therapist->therapistProfile?->cnic_number) }}" class="form-control">
        </div>
    </div>
</div>
<div class="form-section">
    <div class="form-section-title"><i class="fa-solid fa-calendar-days" style="color:var(--teal);"></i> Schedule</div>
    <div class="row g-3">
        <div class="col-12">
            <label>Working Days</label>
            <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:6px;">
                @php $existingDays = old('working_days', $therapist->therapistProfile?->working_days ?? []); @endphp
                @foreach(['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'] as $day)
                    <label style="display:flex;align-items:center;gap:6px;background:var(--bg-light);border:1.5px solid var(--border-soft);padding:7px 14px;border-radius:8px;cursor:pointer;">
                        <input type="checkbox" name="working_days[]" value="{{ $day }}"
                            {{ in_array($day, $existingDays) ? 'checked' : '' }}
                            style="accent-color:var(--teal);">
                        {{ $day }}
                    </label>
                @endforeach
            </div>
        </div>
        @php
            $slotBounds = $therapist->therapistProfile?->inferredSlotBounds() ?? ['start' => '09:00', 'end' => '17:00'];
            $breakTime = $therapist->therapistProfile?->break_time;
            $breakParts = $breakTime ? explode(' - ', $breakTime) : ['13:00', '14:00'];
        @endphp
        <div class="col-md-3">
            <label>Session Start</label>
            <input type="time" name="slot_start" value="{{ old('slot_start', $slotBounds['start']) }}" class="form-control @error('slot_start') is-invalid @enderror">
            @error('slot_start') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-3">
            <label>Session End</label>
            <input type="time" name="slot_end" value="{{ old('slot_end', $slotBounds['end']) }}" class="form-control @error('slot_end') is-invalid @enderror">
            @error('slot_end') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-3">
            <label>Break Start</label>
            <input type="time" name="break_start" value="{{ old('break_start', $breakParts[0] ?? '13:00') }}" class="form-control">
        </div>
        <div class="col-md-3">
            <label>Break End</label>
            <input type="time" name="break_end" value="{{ old('break_end', $breakParts[1] ?? '14:00') }}" class="form-control">
        </div>
        <div class="col-md-4">
            <label>Profile Status</label>
            <select name="profile_status" class="form-control">
                <option value="active" {{ old('profile_status', $therapist->therapistProfile?->status) == 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ old('profile_status', $therapist->therapistProfile?->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
            </select>
        </div>
    </div>
</div>
<div style="display:flex;gap:12px;">
    <button type="submit" class="btn-teal"><i class="fa-solid fa-check"></i> Update Therapist</button>
    <a href="{{ route('therapists.index') }}" class="btn-outline-teal">Cancel</a>
</div>
</form>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce }}">
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
</script>
@endpush
