@extends('layouts.app')
@section('title', 'New Enrollment')
@section('page-title', 'New Enrollment')

@push('styles')
<style>
.schedule-row { background:var(--bg-light); border:1px solid var(--border-soft); border-radius:10px; padding:12px; margin-bottom:8px; display:grid; grid-template-columns:1fr 1fr auto; gap:10px; align-items:end; }
[data-discount-section] { transition: all .3s; }
.enrollment-block-extra {
    margin-top: 24px;
    padding-top: 24px;
    border-top: 2px dashed var(--border-soft);
}
.enrollment-block-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 16px;
}
.enrollment-block-has-error {
    border: 2px solid var(--danger, #dc3545);
    border-radius: 14px;
    padding: 16px;
    background: rgba(220, 53, 69, 0.04);
}
#multiEnrollmentBanner {
    display: none;
}
.enrollment-form-actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 12px;
}
@media (max-width: 991.98px) {
    .enrollment-form-page .enrollment-form-fee-panel {
        position: static;
        top: auto;
    }
    .enrollment-form-page .form-section {
        padding: 18px;
    }
}
@media (max-width: 575.98px) {
    .enrollment-form-page .schedule-row {
        grid-template-columns: 1fr;
    }
    .enrollment-form-page .schedule-row > div:last-child button {
        margin-top: 0;
        width: 100%;
    }
    .enrollment-block-header {
        flex-direction: column;
        align-items: stretch;
    }
    .enrollment-form-actions .btn-teal,
    .enrollment-form-actions .btn-outline-teal {
        width: 100%;
        justify-content: center;
    }
}
</style>
@endpush

@section('content')
<form action="{{ route('enrollments.store') }}" method="POST" enctype="multipart/form-data" class="form-frc" id="enrollForm">
@csrf

<div class="enrollment-form-page">
    <div id="multiEnrollmentBanner" class="alert-frc warning mb-3">
        <i class="fa-solid fa-layer-group"></i>
        <div>
            <strong id="multiEnrollmentBannerTitle">Creating multiple enrollments</strong>
            <div class="small mt-1" id="multiEnrollmentBannerText">Fill session day and time for each enrollment block below, then submit once.</div>
        </div>
    </div>
    <div id="enrollmentBlocks">
        @include('enrollments.partials.enrollment-block', [
            'blockIndex' => 0,
            'showChildren' => true,
            'showRemove' => false,
            'branches' => $branches,
            'services' => $services,
            'initialChildren' => $initialChildren,
        ])

        @foreach(old('extra_enrollments', []) as $extraIndex => $extraData)
            @if(is_array($extraData) && (
                (int) ($extraData['branch_id'] ?? 0) > 0
                || (int) ($extraData['service_id'] ?? 0) > 0
                || (int) ($extraData['therapist_id'] ?? 0) > 0
                || ! empty($extraData['schedules'])
            ))
                @include('enrollments.partials.enrollment-block', [
                    'blockIndex' => (int) $extraIndex,
                    'showChildren' => false,
                    'showRemove' => true,
                    'branches' => $branches,
                    'services' => $services,
                    'initialChildren' => $initialChildren,
                ])
            @endif
        @endforeach
    </div>

    <div class="mt-3 enrollment-form-actions">
        <button type="button" class="btn-outline-teal" id="addAnotherEnrollmentBtn">
            <i class="fa-solid fa-plus"></i> Add Another Enrollment
        </button>
        <button type="submit" class="btn-teal" data-submit-enrollment>
            <i class="fa-solid fa-check"></i> <span data-submit-label>Create Enrollment</span>
        </button>
    </div>
</div>

<template id="extraEnrollmentBlockTemplate">
    @include('enrollments.partials.enrollment-block', [
        'blockIndex' => '__INDEX__',
        'showChildren' => false,
        'showRemove' => true,
        'branches' => $branches,
        'services' => $services,
        'initialChildren' => $initialChildren,
    ])
</template>
</form>
@endsection

@push('scripts')
@include('enrollments.partials.form-scripts', [
    'isEdit' => false,
    'enrollment' => null,
    'excludeEnrollmentId' => null,
    'initialSchedules' => [],
    'initialServiceId' => null,
    'enrollmentPricing' => $enrollmentPricing ?? [],
    'multiBlock' => true,
])
@include('partials.approved-child-picker-scripts', [
    'pickerMode' => 'checkboxes',
    'pickerId' => 'enrollmentChildPicker',
    'initialChildren' => $initialChildren,
])
@endpush
