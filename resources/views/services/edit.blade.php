@extends('layouts.app')
@section('title', 'Edit Service')
@section('page-title', 'Edit Service')
@section('content')
<div class="row justify-content-center g-3 min-w-0">
<div class="col-12 col-md-10 col-lg-8 min-w-0">
<div class="card-frc card-frc--panel">
    <div class="card-header-frc"><h6 class="card-title-frc">Edit Service</h6></div>
    <form action="{{ route('services.update', $service) }}" method="POST" class="form-frc">
        @csrf @method('PUT')
        <div class="mb-3">
            <label>Name <span style="color:var(--danger)">*</span></label>
            <input type="text" name="name" value="{{ old('name', $service->name) }}" class="form-control @error('name') is-invalid @enderror">
            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-3">
            <label>Status</label>
            <select name="status" class="form-control">
                <option value="publish" {{ old('status', $service->status) == 'publish' ? 'selected' : '' }}>Published</option>
                <option value="draft" {{ old('status', $service->status) == 'draft' ? 'selected' : '' }}>Draft</option>
            </select>
        </div>
        @include('services.partials.feedback-questions-field', ['service' => $service])
        <div class="frc-form-actions">
            <button type="submit" class="btn-teal"><i class="fa-solid fa-check"></i> Update</button>
            <a href="{{ route('services.index') }}" class="btn-outline-teal">Cancel</a>
        </div>
    </form>
</div>
</div></div>
@endsection
