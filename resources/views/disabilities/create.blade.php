@extends('layouts.app')
@section('title', 'Add Present Complaint')
@section('page-title', 'Add Present Complaint')

@section('content')
<div class="row justify-content-center g-3 min-w-0">
    <div class="col-12 col-md-8 col-lg-6 min-w-0">
        <div class="card-frc card-frc--panel">
            <div class="card-header-frc">
                <h6 class="card-title-frc"><i class="fa-solid fa-plus me-2" style="color:var(--teal);"></i>Add Present Complaint</h6>
            </div>
            <form action="{{ route('disabilities.store') }}" method="POST" class="form-frc">
                @csrf
                <div class="mb-3">
                    <label>Name <span style="color:var(--danger)">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" placeholder="Present complaint name">
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-4">
                    <label>Status <span style="color:var(--danger)">*</span></label>
                    <select name="status" class="form-control @error('status') is-invalid @enderror">
                        <option value="publish" {{ old('status')==='publish' ? 'selected' : '' }}>Published</option>
                        <option value="draft" {{ old('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                    </select>
                    @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="frc-form-actions">
                    <button type="submit" class="btn-teal"><i class="fa-solid fa-check"></i> Save</button>
                    <a href="{{ route('disabilities.index') }}" class="btn-outline-teal">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
