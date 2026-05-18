@extends('layouts.app')
@section('title', 'Edit Child')
@section('page-title', 'Edit Child')

@section('content')
@php
    $selectedDisabilityIds = old('disability_ids', $child->disabilities->pluck('id')->all());
    $hasOperationalEnrollment = $child->hasOperationalEnrollment();
    $currentStatus = old('status', $child->status);
@endphp
<form action="{{ route('children.update', $child->id) }}" method="POST" class="form-frc">
@csrf
@method('PUT')

<div class="mb-3">
    <a href="{{ route('children.show', $child->id) }}" class="btn-outline-teal" style="font-size:13px;"><i class="fa-solid fa-arrow-left"></i> Back to profile</a>
</div>

<div class="form-section">
    <div class="form-section-title"><i class="fa-solid fa-child" style="color:var(--teal);"></i> Child Information</div>
    <div class="row g-3">
        <div class="col-md-6">
            <label>Full Name <span style="color:var(--danger)">*</span></label>
            <input type="text" name="full_name" value="{{ old('full_name', $child->full_name) }}" class="form-control @error('full_name') is-invalid @enderror">
            @error('full_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-6">
            <label>Father's Name</label>
            <input type="text" name="father_name" value="{{ old('father_name', $child->father_name) }}" class="form-control @error('father_name') is-invalid @enderror">
            @error('father_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-6">
            <label>Email <span style="color:var(--danger)">*</span></label>
            <input type="email" name="email" value="{{ old('email', $child->email) }}" class="form-control @error('email') is-invalid @enderror">
            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-6">
            <label>Account Status <span style="color:var(--danger)">*</span></label>
            <select name="status" class="form-control @error('status') is-invalid @enderror">
                @if($child->status === 'approved')
                    <option value="approved" {{ $currentStatus === 'approved' ? 'selected' : '' }}>Approved</option>
                @endif
                @if($hasOperationalEnrollment || $child->status === 'active')
                    <option value="active" {{ $currentStatus === 'active' ? 'selected' : '' }}>Active</option>
                @endif
                <option value="pending" {{ $currentStatus === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="inactive" {{ $currentStatus === 'inactive' ? 'selected' : '' }}>Inactive</option>
                <option value="rejected" {{ $currentStatus === 'rejected' ? 'selected' : '' }}>Rejected</option>
            </select>
            @error('status') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            @if(! $hasOperationalEnrollment && $child->status !== 'active')
                <div style="font-size:12px;color:var(--text-muted);margin-top:6px;">
                    <strong>Active</strong> appears here after the child has at least one enrollment (Approved / Active / Completed).
                    <strong>Approved</strong> is set only from <em>Pending Approvals</em>, not from this dropdown.
                </div>
            @elseif($child->status === 'active' && ! $hasOperationalEnrollment)
                <div class="alert-frc warning mt-2" style="padding:10px 14px;font-size:12px;">
                    This account is Active but no qualifying enrollment was found. Save another status or restore an enrollment.
                </div>
            @endif
        </div>
        <div class="col-md-4">
            <label>Age</label>
            <input type="number" name="age" value="{{ old('age', $child->age) }}" class="form-control @error('age') is-invalid @enderror" min="0" max="120">
            @error('age') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-4">
            <label>Gender</label>
            <select name="gender" class="form-control">
                <option value="">Select</option>
                <option value="male" {{ old('gender', $child->gender) === 'male' ? 'selected' : '' }}>Male</option>
                <option value="female" {{ old('gender', $child->gender) === 'female' ? 'selected' : '' }}>Female</option>
                <option value="other" {{ old('gender', $child->gender) === 'other' ? 'selected' : '' }}>Other</option>
            </select>
        </div>
        <div class="col-md-4">
            <label>Date of Birth</label>
            <input type="date" name="date_of_birth" value="{{ old('date_of_birth', $child->date_of_birth?->format('Y-m-d')) }}" class="form-control @error('date_of_birth') is-invalid @enderror">
            @error('date_of_birth') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-6">
            <label>Phone</label>
            <input type="text" name="phone_number" value="{{ old('phone_number', $child->phone_number) }}" class="form-control">
        </div>
        <div class="col-md-6">
            <label>WhatsApp</label>
            <input type="text" name="whatsapp_number" value="{{ old('whatsapp_number', $child->whatsapp_number) }}" class="form-control">
        </div>
        <div class="col-12">
            <label>Address</label>
            <textarea name="address" class="form-control" rows="2">{{ old('address', $child->address) }}</textarea>
        </div>
        <div class="col-12">
            <label>Parent Notes</label>
            <textarea name="parent_notes" class="form-control" rows="3">{{ old('parent_notes', $child->parent_notes) }}</textarea>
        </div>
    </div>
</div>

<div class="form-section">
    <div class="form-section-title"><i class="fa-solid fa-heart-pulse" style="color:var(--teal);"></i> Disabilities</div>
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
</div>

<div class="form-section">
    <div class="form-section-title"><i class="fa-solid fa-key" style="color:var(--teal);"></i> Password</div>
    <div class="row g-3">
        <div class="col-md-6">
            <label>New Password <span style="color:var(--text-muted);font-weight:400;font-size:12px;">(optional)</span></label>
            <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" autocomplete="new-password">
            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-6">
            <label>Confirm Password</label>
            <input type="password" name="password_confirmation" class="form-control" autocomplete="new-password">
        </div>
    </div>
</div>

<div style="display:flex;gap:12px;flex-wrap:wrap;">
    <button type="submit" class="btn-teal"><i class="fa-solid fa-save"></i> Save Changes</button>
    <a href="{{ route('children.show', $child->id) }}" class="btn-outline-teal">Cancel</a>
</div>
</form>
@endsection
