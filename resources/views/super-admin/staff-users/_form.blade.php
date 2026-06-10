@php
    $isEdit = isset($user);
    $u = $user ?? null;
    $selectedRole = old('role', $u?->role?->name);
    $showBranchField = $selectedRole === 'admin';
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
        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="e.g. Admin@123" @if(!$isEdit) required minlength="8" @endif autocomplete="new-password">
        @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <div class="form-text">At least 8 characters, with letters and numbers.</div>
    </div>
    <div class="col-md-6">
        <label class="form-label">Confirm password @if(!$isEdit)<span class="text-danger">*</span>@endif</label>
        <input type="password" name="password_confirmation" class="form-control @error('password') is-invalid @enderror" @if(!$isEdit) required minlength="8" @endif autocomplete="new-password">
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
        <input type="date" name="date_of_birth"
            class="form-control @error('date_of_birth') is-invalid @enderror"
            value="{{ old('date_of_birth', $u?->date_of_birth?->format('Y-m-d')) }}"
            max="{{ now()->format('Y-m-d') }}">
        @error('date_of_birth') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12">
        <label class="form-label">Address</label>
        <textarea name="address" class="form-control" rows="2" maxlength="2000">{{ old('address', $u?->address) }}</textarea>
    </div>
    <div class="col-12">
        <label class="form-label">Role <span class="text-danger">*</span></label>
        <select name="role" id="staffRoleSelect" class="form-select" required>
            <option value="">Select role</option>
            <option value="admin" @selected($selectedRole === 'admin')>Admin</option>
            <option value="finance" @selected($selectedRole === 'finance')>Finance</option>
            <option value="approval_discount" @selected($selectedRole === 'approval_discount')>Approval Discount</option>
        </select>
        <div class="form-text">Admin is linked to a branch. Finance and Approval Discount are organization-wide (no branch). Approval Discount users can only access the High Discount queue.</div>
    </div>
    <div class="col-12" id="staffBranchField" @if(!$showBranchField) style="display:none;" @endif>
        <label class="form-label">Branch <span class="text-danger">*</span></label>
        <select name="branch_id" id="staffBranchSelect" class="form-select @error('branch_id') is-invalid @enderror" @if($showBranchField) required @endif>
            <option value="">Select branch</option>
            @foreach($branches ?? [] as $branch)
                <option value="{{ $branch->id }}" @selected((string) old('branch_id', $u?->branch_id) === (string) $branch->id)>{{ $branch->displayLabel() }}</option>
            @endforeach
        </select>
        @error('branch_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <div class="form-text">Select which branch this admin will manage.</div>
    </div>
</div>

@push('scripts')
<script src="{{ asset('js/frc-staff-user-form.js') }}" nonce="{{ $cspNonce }}"></script>
@endpush
