@extends('layouts.app')
@section('title', 'Edit Present Complaint')
@section('page-title', 'Edit Present Complaint')

@section('content')
<div class="row justify-content-center g-3 min-w-0">
    <div class="col-12 col-md-8 col-lg-6 min-w-0">
        <div class="card-frc card-frc--panel">
            <div class="card-header-frc">
                <h6 class="card-title-frc"><i class="fa-solid fa-pen me-2" style="color:var(--teal);"></i>Edit Present Complaint</h6>
            </div>
            <form action="{{ route('disabilities.update', $disability) }}" method="POST" class="form-frc">
                @csrf @method('PUT')
                <div class="mb-3">
                    <label>Name <span style="color:var(--danger)">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $disability->name) }}" class="form-control @error('name') is-invalid @enderror">
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-4">
                    <label>Status <span style="color:var(--danger)">*</span></label>
                    <select name="status" class="form-control @error('status') is-invalid @enderror">
                        <option value="publish" {{ old('status', $disability->status) == 'publish' ? 'selected' : '' }}>Published</option>
                        <option value="draft" {{ old('status', $disability->status) == 'draft' ? 'selected' : '' }}>Draft</option>
                    </select>
                    @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="frc-form-actions">
                    <button type="submit" class="btn-teal"><i class="fa-solid fa-check"></i> Update</button>
                    <a href="{{ route('disabilities.index') }}" class="btn-outline-teal">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
