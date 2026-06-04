@extends('layouts.app')
@section('title', 'New Enrollment')
@section('page-title', 'New Enrollment')

@push('styles')
<style>
.schedule-row { background:var(--bg-light); border:1px solid var(--border-soft); border-radius:10px; padding:12px; margin-bottom:8px; display:grid; grid-template-columns:1fr 1fr auto; gap:10px; align-items:end; }
#discountSection { transition: all .3s; }
</style>
@endpush

@section('content')
<form action="{{ route('enrollments.store') }}" method="POST" enctype="multipart/form-data" class="form-frc" id="enrollForm">
@csrf

<div class="row g-3">
    {{-- Left Column --}}
    <div class="col-md-8">
        {{-- Child + Branch + Therapist --}}
        <div class="form-section">
            <div class="form-section-title"><i class="fa-solid fa-children" style="color:var(--teal);"></i> Children & Therapist</div>
            <div class="row g-3">
                <div class="col-12">
                    <label class="d-block mb-2">Children <span style="color:var(--danger)">*</span></label>
                    @error('child_ids') <div class="invalid-feedback d-block mb-2">{{ $message }}</div> @enderror
                    @error('child_ids.*') <div class="invalid-feedback d-block mb-2">{{ $message }}</div> @enderror
                    @include('partials.approved-child-checkboxes-field', [
                        'pickerId' => 'enrollmentChildPicker',
                        'label' => false,
                        'showLabel' => false,
                        'initialChildren' => $initialChildren,
                        'selectedIds' => old('child_ids', request()->query('child_id') ? [(int) request()->query('child_id')] : []),
                    ])
                    <small class="text-muted d-block mt-1">Select one child for individual therapy, or two or more for group therapy in the same slot.</small>
                </div>
                <div class="col-md-6">
                    <label>Branch <span style="color:var(--danger)">*</span></label>
                    @if($branches->count() === 1)
                        @php $onlyBranch = $branches->first(); @endphp
                        <input type="hidden" name="branch_id" id="branchSelect" value="{{ $onlyBranch->id }}">
                        <input type="text" class="form-control" value="{{ $onlyBranch->displayLabel() }}" readonly>
                    @else
                        <select name="branch_id" id="branchSelect" class="form-control @error('branch_id') is-invalid @enderror" onchange="loadTherapists(this.value)" required>
                            <option value="">Select Branch</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ (string) old('branch_id') === (string) $branch->id ? 'selected' : '' }}>{{ $branch->displayLabel() }}</option>
                            @endforeach
                        </select>
                    @endif
                    @error('branch_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label>Service <span style="color:var(--danger)">*</span></label>
                    <select name="service_id" id="serviceSelect" class="form-control @error('service_id') is-invalid @enderror" onchange="onEnrollmentServiceChange()">
                        <option value="">Select Service</option>
                        @foreach($services as $svc)
                            <option value="{{ $svc->id }}" {{ (int) old('service_id') === $svc->id ? 'selected' : '' }}>{{ $svc->name }}</option>
                        @endforeach
                    </select>
                    @error('service_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label>Therapist <span style="color:var(--danger)">*</span></label>
                    <select name="therapist_id" id="therapistSelect" class="form-control @error('therapist_id') is-invalid @enderror" onchange="loadScheduleOptions()">
                        <option value="">Select branch &amp; service first</option>
                    </select>
                    @error('therapist_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <option value="draft" @selected(old('status', 'draft') === 'draft')>Draft</option>
                        <option value="active" @selected(old('status', 'draft') === 'active')>Active</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Schedule --}}
        <div class="form-section">
            <div class="form-section-title"><i class="fa-solid fa-calendar-days" style="color:var(--teal);"></i> Session Schedule</div>
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label>First session starts on <span style="color:var(--danger)">*</span></label>
                    <input type="date" name="schedule_start_date" id="scheduleStartDate"
                        class="form-control @error('schedule_start_date') is-invalid @enderror"
                        value="{{ old('schedule_start_date', now()->toDateString()) }}"
                        min="{{ now()->toDateString() }}" required>
                    @error('schedule_start_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <p class="text-muted small mb-0 mt-1" id="firstSessionHint">Pick a start date — the matching weekday is added to the schedule when the therapist works that day; then choose a time slot.</p>
                </div>
            </div>
            <div id="scheduleRows">
                <div class="schedule-row">
                    <div>
                        <label>Day <span style="color:var(--danger)">*</span></label>
                        <select name="schedules[0][day]" class="form-control" id="daySelect0">
                            <option value="">Select Day</option>
                        </select>
                    </div>
                    <div>
                        <label>Time Slot <span style="color:var(--danger)">*</span></label>
                        <select name="schedules[0][time_slot]" class="form-control" id="slotSelect0">
                            <option value="">Select Day First</option>
                        </select>
                    </div>
                    <div style="padding-bottom:2px;">
                        <button type="button" onclick="removeRow(this)" style="background:var(--danger);color:#fff;border:none;border-radius:8px;padding:8px 12px;cursor:pointer;margin-top:20px;"><i class="fa-solid fa-minus"></i></button>
                    </div>
                </div>
            </div>
            <button type="button" class="btn-outline-teal mt-2" onclick="addScheduleRow()">
                <i class="fa-solid fa-plus"></i> Add Another Day/Slot
            </button>
        </div>

        {{-- Duration --}}
        <div class="form-section">
            <div class="form-section-title"><i class="fa-solid fa-clock" style="color:var(--teal);"></i> Duration</div>
            <div class="row g-3">
                <div class="col-12">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                        <input type="checkbox" name="repeat_weekly" value="1" id="repeatWeekly"
                            {{ old('repeat_weekly') ? 'checked' : '' }}
                            onchange="toggleDuration(); recalculate();" style="accent-color:var(--teal);">
                        Repeat Weekly (auto-generate sessions)
                    </label>
                </div>
                <div id="durationSection" style="display:none;" class="col-12">
                    <div class="row g-3">
                        <div class="col-6">
                            <label>Duration Value</label>
                            <input type="number" name="duration_value" id="durationValue" value="{{ old('duration_value') }}" class="form-control" min="1" placeholder="e.g. 3" oninput="recalculate()">
                        </div>
                        <div class="col-6">
                            <label>Duration Unit</label>
                            <select name="duration_unit" id="durationUnit" class="form-control" onchange="recalculate()">
                                <option value="weekly" {{ old('duration_unit', 'weekly') === 'weekly' ? 'selected' : '' }}>Weekly</option>
                                <option value="monthly" {{ old('duration_unit') === 'monthly' ? 'selected' : '' }}>Monthly</option>
                                <option value="yearly" {{ old('duration_unit') === 'yearly' ? 'selected' : '' }}>Yearly</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Right Column: Fee Calculation --}}
    <div class="col-md-4">
        <div class="form-section" style="position:sticky;top:80px;">
            <div class="form-section-title"><i class="fa-solid fa-calculator" style="color:var(--teal);"></i> Fee Calculation</div>
            <div class="mb-3">
                <label>Price Per Session (PKR) <span style="color:var(--danger)">*</span></label>
                <input type="number" name="price_per_session" id="pricePerSession" value="{{ old('price_per_session', 0) }}"
                    class="form-control" min="0" step="1" readonly tabindex="-1"
                    style="background:var(--bg-light);cursor:not-allowed;">
                <small id="sessionPriceHint" class="text-muted d-block mt-1" style="font-size:12px;"></small>
            </div>
            <div class="mb-3">
                <label>Discount % (0–100)</label>
                <input type="number" name="discount_percentage" id="discountPct" value="{{ old('discount_percentage', 0) }}"
                    class="form-control" min="0" max="100" step="1" oninput="recalculate(); checkHighDiscount()">
            </div>
            <div style="background:var(--bg-light);border-radius:12px;padding:16px;margin-bottom:16px;">
                <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:8px;">
                    <span style="color:var(--text-muted);">Sessions (auto):</span>
                    <strong id="calcSessions">0</strong>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:8px;">
                    <span style="color:var(--text-muted);">Subtotal:</span>
                    <strong>PKR <span id="calcSubtotal">0</span></strong>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:8px;">
                    <span style="color:var(--text-muted);">Discount:</span>
                    <strong style="color:var(--danger);">- PKR <span id="calcDiscount">0</span></strong>
                </div>
                <hr style="border-color:var(--border-soft);">
                <div style="display:flex;justify-content:space-between;font-size:16px;font-family:'Poppins',sans-serif;font-weight:700;">
                    <span>Total:</span>
                    <span style="color:var(--teal);">PKR <span id="calcTotal">0</span></span>
                </div>
            </div>

            {{-- High Discount Section --}}
            <div id="discountSection" style="display:none;">
                <div class="alert-frc warning mb-3">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <div><strong>High Discount (&gt;{{ $frc['high_discount_threshold'] }}%)</strong> requires approval. Provide a reason (supporting document optional).</div>
                </div>
                <div class="mb-3">
                    <label>Discount Reason <span style="color:var(--danger)">*</span></label>
                    <textarea name="discount_reason" class="form-control @error('discount_reason') is-invalid @enderror" rows="3" placeholder="Why is this discount being given?">{{ old('discount_reason') }}</textarea>
                    @error('discount_reason') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label>Support Document <span style="color:var(--text-muted);font-weight:normal;font-size:12px;">(optional)</span></label>
                    <input type="file" name="discount_file" class="form-control @error('discount_file') is-invalid @enderror" accept=".pdf,.jpg,.jpeg,.png,.webp">
                    @error('discount_file') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            <button type="submit" class="btn-teal mt-3" style="width:100%;padding:11px;justify-content:center;">
                <i class="fa-solid fa-check"></i> Create Enrollment
            </button>
        </div>
    </div>
</div>
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
])
@include('partials.approved-child-picker-scripts', [
    'pickerMode' => 'checkboxes',
    'pickerId' => 'enrollmentChildPicker',
    'initialChildren' => $initialChildren,
])
@endpush
