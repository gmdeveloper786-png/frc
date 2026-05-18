@php
    $isEdit = isset($user);
    $u = $user ?? null;
@endphp

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Full name <span class="text-danger">*</span></label>
        <input type="text" name="full_name" class="form-control" required maxlength="255" value="{{ old('full_name', $u?->full_name) }}">
    </div>
    <div class="col-md-6">
        <label class="form-label">Father name</label>
        <input type="text" name="father_name" class="form-control" maxlength="255" value="{{ old('father_name', $u?->father_name) }}">
    </div>
    <div class="col-md-6">
            <label class="form-label">Status <span class="text-danger">*</span></label>
            <select name="status" class="form-select" required>
                <option value="active" @selected(old('status', $u?->status) === 'active')>Active</option>
                <option value="inactive" @selected(old('status', $u?->status) === 'inactive')>Inactive</option>
            </select>
        </div>
    <div class="col-md-6">
        <label class="form-label">Email <span class="text-danger">*</span></label>
        <input type="email" name="email" class="form-control" required value="{{ old('email', $u?->email) }}">
    </div>
    <div class="col-md-6">
        <label class="form-label">Password @if(!$isEdit)<span class="text-danger">*</span>@else<span class="text-muted small">(leave blank to keep)</span>@endif</label>
        <input type="password" name="password" class="form-control" @if(!$isEdit) required minlength="8" @endif autocomplete="new-password">
    </div>
    <div class="col-md-6">
        <label class="form-label">Confirm password @if(!$isEdit)<span class="text-danger">*</span>@endif</label>
        <input type="password" name="password_confirmation" class="form-control" @if(!$isEdit) required minlength="8" @endif autocomplete="new-password">
    </div>
    <div class="col-md-6">
        <label class="form-label">Phone number</label>
        <input type="text" name="phone_number" class="form-control" maxlength="30" value="{{ old('phone_number', $u?->phone_number) }}">
    </div>
    <div class="col-md-6">
        <label class="form-label">WhatsApp number</label>
        <input type="text" name="whatsapp_number" class="form-control" maxlength="30" value="{{ old('whatsapp_number', $u?->whatsapp_number) }}">
    </div>
    <div class="col-md-6">
        <label class="form-label">Gender</label>
        <select name="gender" class="form-select">
            <option value="">—</option>
            <option value="male" @selected(old('gender', $u?->gender) === 'male')>Male</option>
            <option value="female" @selected(old('gender', $u?->gender) === 'female')>Female</option>
            <option value="other" @selected(old('gender', $u?->gender) === 'other')>Other</option>
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label">Date of birth</label>
        <input type="date" name="date_of_birth" class="form-control" value="{{ old('date_of_birth', $u?->date_of_birth?->format('Y-m-d')) }}">
    </div>

    <div class="col-12">
        <label class="form-label">Address</label>
        <textarea name="address" class="form-control" rows="2" maxlength="2000">{{ old('address', $u?->address) }}</textarea>
    </div>
    <div class="col-12">
        <label class="form-label">Role <span class="text-danger">*</span></label>
        <select name="role" class="form-select" required>
            <option value="">Select role</option>
            <option value="admin" @selected(old('role', $u?->role?->name) === 'admin')>Admin</option>
            <option value="finance" @selected(old('role', $u?->role?->name) === 'finance')>Finance</option>
        </select>
        <div class="form-text">Only Admin or Finance can be assigned from this form.</div>
    </div>
</div>
