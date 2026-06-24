@extends('layouts.app')
@section('title', 'Schedule Assessment')
@section('page-title', 'Schedule Assessment')

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-lg-9">
        <form action="{{ route('assessments.store') }}" method="POST" class="form-frc" id="assessmentForm">
        @csrf

        <div class="form-section">
            <div class="form-section-title"><i class="fa-solid fa-children" style="color:var(--teal);"></i> Assign Children
            </div>
            @include('partials.approved-child-checkboxes-field', [
            'initialChildren' => $initialChildren,
            'selectedIds' => old('child_ids', []),
            ])
        </div>

        <div class="form-section">
            <div class="form-section-title"><i class="fa-solid fa-clipboard-list" style="color:var(--teal);"></i> Assessment Details</div>
            <div class="row g-3">
                <div class="col-md-4">
                    <label>Date <span style="color:var(--danger)">*</span></label>
                    <input type="date" name="date" id="assessDate" value="{{ old('date') }}"
                        min="{{ now()->format('Y-m-d') }}"
                        class="form-control @error('date') is-invalid @enderror">
                    @error('date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label>Day (auto) <span style="color:var(--danger)">*</span></label>
                    <input type="text" id="dayDisplay" class="form-control" readonly placeholder="Auto-calculated" style="background:var(--bg-light);">
                </div>
                <div class="col-md-4">
                    <label>Time <span style="color:var(--danger)">*</span></label>
                    <input type="time" name="time" id="assessTime" value="{{ old('time') }}" class="form-control @error('time') is-invalid @enderror">
                    @error('time') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label>Branch <span style="color:var(--danger)">*</span></label>
                    @if($branches->count() === 1)
                        @php $onlyBranch = $branches->first(); @endphp
                        <input type="hidden" name="branch_id" id="assessmentBranchSelect" value="{{ $onlyBranch->id }}">
                        <input type="text" class="form-control" value="{{ $onlyBranch->displayLabel() }}" readonly>
                    @else
                        <select name="branch_id" id="assessmentBranchSelect" class="form-control @error('branch_id') is-invalid @enderror" required>
                            <option value="">Select Branch</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ (string) old('branch_id') === (string) $branch->id ? 'selected' : '' }}>{{ $branch->displayLabel() }}</option>
                            @endforeach
                        </select>
                    @endif
                    @error('branch_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label>Service <span style="color:var(--danger)">*</span></label>
                    <select name="service_id" id="assessmentServiceSelect" class="form-control @error('service_id') is-invalid @enderror">
                        <option value="">Select Service</option>
                        @foreach($services as $svc)
                            <option value="{{ $svc->id }}" {{ (int) old('service_id') === $svc->id ? 'selected' : '' }}>{{ $svc->name }}</option>
                        @endforeach
                    </select>
                    @error('service_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label>Status <span style="color:var(--danger)">*</span></label>
                    <select name="status" class="form-control">
                        <option value="publish" {{ old('status') === 'publish' ? 'selected' : '' }}>Scheduled</option>
                        <option value="draft" {{ old('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="form-section">
            <div class="form-section-title"><i class="fa-solid fa-user-doctor" style="color:var(--teal);"></i> Therapist </div>
            @error('therapist_id') <div class="invalid-feedback d-block mb-2">{{ $message }}</div> @enderror
            <div class="row g-3">
                <div class="col-12">
                    <label>Therapist <span style="color:var(--danger)">*</span></label>
                    <select name="therapist_id" id="assessmentTherapistSelect" class="form-control">
                        <option value="">Select branch &amp; service first</option>
                    </select>
                    <div id="assessmentTherapistHint" style="display:none;margin-top:8px;font-size:13px;color:var(--danger);">
                    </div>
                    <small style="color:var(--text-muted);font-size:12px;display:block;margin-top:6px;">Required when status is
                        Scheduled. Draft assessments may leave therapist unset. Therapists are filtered by branch and service.</small>
                </div>
            </div>
        </div>

        <div class="frc-form-actions">
            <button type="submit" class="btn-teal"><i class="fa-solid fa-check"></i> Create Assessment</button>
            <a href="{{ route('assessments.index') }}" class="btn-outline-teal">Cancel</a>
        </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
@include('assessments.partials.therapist-select-scripts')
@include('assessments.partials.assessment-datetime-scripts')
@include('partials.approved-child-picker-scripts', [
    'pickerMode' => 'checkboxes',
    'initialChildren' => $initialChildren,
    'pickerId' => 'approvedChildPicker',
])
@endpush
