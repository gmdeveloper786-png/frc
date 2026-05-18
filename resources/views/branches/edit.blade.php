@extends('layouts.app')
@section('title', 'Edit Branch')
@section('page-title', 'Edit Branch')
@section('content')
<div class="row justify-content-center"><div class="col-md-7">
<div class="card-frc">
    <div class="card-header-frc"><h6 class="card-title-frc">Edit Branch</h6></div>
    <form action="{{ route('branches.update', $branch) }}" method="POST" class="form-frc">
        @csrf @method('PUT')
        <div class="row g-3">
            <div class="col-md-8">
                <label>Branch Name <span style="color:var(--danger)">*</span></label>
                <input type="text" name="name" value="{{ old('name', $branch->name) }}" class="form-control @error('name') is-invalid @enderror">
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4">
                <label>City</label>
                <input type="text" name="city" value="{{ old('city', $branch->city) }}" class="form-control">
            </div>
            <div class="col-12">
                <label>Address</label>
                <textarea name="address" class="form-control" rows="2">{{ old('address', $branch->address) }}</textarea>
            </div>
            <div class="col-md-6">
                <label>Phone</label>
                <input type="text" name="phone" value="{{ old('phone', $branch->phone) }}" class="form-control">
            </div>
            <div class="col-md-6">
                <label>Status</label>
                <select name="status" class="form-control">
                    <option value="draft" {{ old('status', $branch->status) == 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="publish" {{ old('status', $branch->status) == 'publish' ? 'selected' : '' }}>Published</option>
                </select>
            </div>
        </div>
        <div style="display:flex;gap:10px;margin-top:20px;">
            <button type="submit" class="btn-teal"><i class="fa-solid fa-check"></i> Update</button>
            <a href="{{ route('branches.index') }}" class="btn-outline-teal">Cancel</a>
        </div>
    </form>
</div>
</div></div>
@endsection
