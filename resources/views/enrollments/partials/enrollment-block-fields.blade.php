<div class="col-12 col-sm-6">
    <label>Branch <span style="color:var(--danger)">*</span></label>
    @if($branches->count() === 1)
        @php $onlyBranch = $branches->first(); @endphp
        <input type="hidden" name="{{ $fieldName('branch_id') }}" data-branch-select value="{{ $onlyBranch->id }}">
        <input type="text" class="form-control" value="{{ $onlyBranch->displayLabel() }}" readonly>
    @else
        <select name="{{ $fieldName('branch_id') }}" data-branch-select class="form-control @error($fieldError('branch_id')) is-invalid @enderror" required>
            <option value="">Select Branch</option>
            @foreach($branches as $branch)
                <option value="{{ $branch->id }}" {{ (string) $fieldOld('branch_id') === (string) $branch->id ? 'selected' : '' }}>{{ $branch->displayLabel() }}</option>
            @endforeach
        </select>
    @endif
    @error($fieldError('branch_id')) <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
<div class="col-12 col-sm-6">
    <label>Service <span style="color:var(--danger)">*</span></label>
    <select name="{{ $fieldName('service_id') }}" data-service-select class="form-control @error($fieldError('service_id')) is-invalid @enderror">
        <option value="">Select Service</option>
        @foreach($services as $svc)
            <option value="{{ $svc->id }}" {{ (int) $fieldOld('service_id') === $svc->id ? 'selected' : '' }}>{{ $svc->name }}</option>
        @endforeach
    </select>
    @error($fieldError('service_id')) <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
<div class="col-12 col-sm-6">
    <label>Therapist <span style="color:var(--danger)">*</span></label>
    <select name="{{ $fieldName('therapist_id') }}" data-therapist-select class="form-control @error($fieldError('therapist_id')) is-invalid @enderror">
        <option value="">Select branch &amp; service first</option>
    </select>
    @error($fieldError('therapist_id')) <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
<div class="col-12 col-sm-6">
    <label>Status</label>
    <select name="{{ $fieldName('status') }}" class="form-control">
        <option value="active" @selected($fieldOld('status', 'active') === 'active')>Active</option>
        <option value="draft" @selected($fieldOld('status', 'active') === 'draft')>Draft</option>
    </select>
</div>
