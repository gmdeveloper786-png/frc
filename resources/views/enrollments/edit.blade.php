@extends('layouts.app')
@section('title', 'Edit Enrollment')
@section('page-title', 'Edit Enrollment')

@push('styles')
<style>
.schedule-row { background:var(--bg-light); border:1px solid var(--border-soft); border-radius:10px; padding:12px; margin-bottom:8px; display:grid; grid-template-columns:1fr 1fr auto; gap:10px; align-items:end; }
#discountSection { transition: all .3s; }
</style>
@endpush

@section('content')
<form action="{{ route('enrollments.update', $enrollment->id) }}" method="POST" enctype="multipart/form-data" class="form-frc" id="enrollForm">
@csrf
@method('PUT')

<div class="row g-3">
    <div class="col-md-8">
        <div class="form-section">
            <div class="form-section-title"><i class="fa-solid fa-user" style="color:var(--teal);"></i> Child & Therapist</div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label>Child</label>
                    <input type="hidden" name="child_id" value="{{ old('child_id', $enrollment->child_id) }}">
                    <select class="form-control" disabled style="background:var(--bg-light);">
                        <option>{{ $enrollment->child?->full_name ?? '—' }}</option>
                    </select>
                    @error('child_id') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label>Branch <span style="color:var(--danger)">*</span></label>
                    @if($branches->count() === 1)
                        @php $onlyBranch = $branches->first(); @endphp
                        <input type="hidden" name="branch_id" id="branchSelect" value="{{ $onlyBranch->id }}">
                        <input type="text" class="form-control" value="{{ $onlyBranch->displayLabel() }}" readonly>
                    @else
                        <select name="branch_id" id="branchSelect" class="form-control @error('branch_id') is-invalid @enderror" required>
                            <option value="">Select Branch</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ (string) old('branch_id', $enrollment->branch_id) === (string) $branch->id ? 'selected' : '' }}>{{ $branch->displayLabel() }}</option>
                            @endforeach
                        </select>
                    @endif
                    @error('branch_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label>Service <span style="color:var(--danger)">*</span></label>
                    <select name="service_id" id="serviceSelect" class="form-control @error('service_id') is-invalid @enderror">
                        <option value="">Select Service</option>
                        @foreach($services as $svc)
                            <option value="{{ $svc->id }}" {{ (int) old('service_id', $enrollment->service_id) === $svc->id ? 'selected' : '' }}>{{ $svc->name }}</option>
                        @endforeach
                    </select>
                    @error('service_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label>Therapist <span style="color:var(--danger)">*</span></label>
                    <select name="therapist_id" id="therapistSelect" class="form-control @error('therapist_id') is-invalid @enderror">
                        <option value="">Select branch &amp; service first</option>
                    </select>
                    @error('therapist_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                @php
                    $canSetPendingSuperAdmin = auth()->user()?->isSuperAdmin();
                    // "Approved" is set via Approve action, not this form — keep it only when already approved so saves stay valid.
                    $statusOptions = $canSetPendingSuperAdmin
                        ? ['draft', 'pending_super_admin_approval', 'rejected', 'active', 'completed', 'cancelled']
                        : ['draft', 'rejected', 'active', 'completed', 'cancelled'];
                    if ($enrollment->status === 'approved') {
                        $i = array_search('draft', $statusOptions, true);
                        if ($i !== false) {
                            array_splice($statusOptions, $i + 1, 0, ['approved']);
                        } else {
                            array_unshift($statusOptions, 'approved');
                        }
                    }
                    $lockedPendingSuperAdmin = ! $canSetPendingSuperAdmin && $enrollment->status === 'pending_super_admin_approval';
                @endphp
                <div class="col-md-6">
                    <label>Status</label>
                    @if($lockedPendingSuperAdmin)
                        <input type="hidden" name="status" value="pending_super_admin_approval">
                        <input type="text" class="form-control" value="Pending super admin approval" disabled style="background:var(--bg-light);">
                        <p class="text-muted small mb-0 mt-1">Only Super Admin can change this status. You can still update schedule and fees.</p>
                    @else
                        <select name="status" class="form-control">
                            @foreach($statusOptions as $st)
                                <option value="{{ $st }}" {{ old('status', $enrollment->status) === $st ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $st)) }}</option>
                            @endforeach
                        </select>
                    @endif
                    @error('status') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>

        <div class="form-section">
            <div class="form-section-title"><i class="fa-solid fa-calendar-days" style="color:var(--teal);"></i> Session Schedule</div>
            @php
                $scheduleStartDefault = old('schedule_start_date', $enrollment->schedule_start_date?->toDateString() ?? $enrollment->created_at?->toDateString());
            @endphp
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label>First session starts on <span style="color:var(--danger)">*</span></label>
                    <input type="date" name="schedule_start_date" id="scheduleStartDate"
                        class="form-control @error('schedule_start_date') is-invalid @enderror"
                        value="{{ $scheduleStartDefault }}"
                        min="{{ now()->toDateString() }}" required>
                    @error('schedule_start_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <p class="text-muted small mb-0 mt-1" id="firstSessionHint">Pick a start date — the matching weekday is added to the schedule when the therapist works that day; then choose a time slot.</p>
                </div>
            </div>
            <div id="scheduleRows"></div>
            <button type="button" class="btn-outline-teal mt-2" id="addScheduleRowBtn">
                <i class="fa-solid fa-plus"></i> Add Another Day/Slot
            </button>
            @error('schedules') <div class="text-danger small mt-2">{{ $message }}</div> @enderror
        </div>

        <div class="form-section">
            <div class="form-section-title"><i class="fa-solid fa-clock" style="color:var(--teal);"></i> Duration</div>
            <div class="row g-3">
                <div class="col-12">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                        <input type="checkbox" name="repeat_weekly" value="1" id="repeatWeekly"
                            {{ old('repeat_weekly', $enrollment->repeat_weekly) ? 'checked' : '' }}
                            style="accent-color:var(--teal);">
                        Repeat Weekly (auto-generate sessions)
                    </label>
                </div>
                <div id="durationSection" style="display:none;" class="col-12">
                    <div class="row g-3">
                        <div class="col-6">
                            <label>Duration Value</label>
                            <input type="number" name="duration_value" id="durationValue" value="{{ old('duration_value', $enrollment->duration_value) }}" class="form-control" min="1" placeholder="e.g. 3">
                        </div>
                        <div class="col-6">
                            <label>Duration Unit</label>
                            <select name="duration_unit" id="durationUnit" class="form-control">
                                <option value="weekly" {{ old('duration_unit', $enrollment->duration_unit ?? 'weekly') === 'weekly' ? 'selected' : '' }}>Weekly</option>
                                <option value="monthly" {{ old('duration_unit', $enrollment->duration_unit ?? '') === 'monthly' ? 'selected' : '' }}>Monthly</option>
                                <option value="yearly" {{ old('duration_unit', $enrollment->duration_unit ?? '') === 'yearly' ? 'selected' : '' }}>Yearly</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="form-section" style="position:sticky;top:80px;">
            <div class="form-section-title"><i class="fa-solid fa-calculator" style="color:var(--teal);"></i> Fee Calculation</div>
            <div class="mb-3">
                <label>Price Per Session (PKR) <span style="color:var(--danger)">*</span></label>
                <input type="number" name="price_per_session" id="pricePerSession" value="{{ old('price_per_session', frc_money_input($enrollment->price_per_session)) }}"
                    class="form-control" min="0" step="1" readonly tabindex="-1"
                    style="background:var(--bg-light);cursor:not-allowed;">
                <small id="sessionPriceHint" class="text-muted d-block mt-1" style="font-size:12px;"></small>
                @error('price_per_session') <div class="text-danger small">{{ $message }}</div> @enderror
            </div>
            <div class="mb-3">
                <label>Discount % (0–100)</label>
                <input type="number" name="discount_percentage" id="discountPct" value="{{ old('discount_percentage', frc_percent($enrollment->discount_percentage)) }}"
                    class="form-control" min="0" max="100" step="1">
            </div>
            @include('enrollments.partials.zakat-eligibility-field', ['selected' => $enrollment->zakat_eligibility])
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

            <div id="discountSection" style="display:none;">
                <div class="alert-frc warning mb-3">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <div><strong>High Discount (&gt;{{ $frc['high_discount_threshold'] }}%)</strong> requires approval. Provide a reason (supporting document optional).</div>
                </div>
                <div class="mb-3">
                    <label>Discount Reason <span style="color:var(--danger)">*</span></label>
                    <textarea name="discount_reason" class="form-control @error('discount_reason') is-invalid @enderror" rows="3" placeholder="Why is this discount being given?">{{ old('discount_reason', $enrollment->discount_reason) }}</textarea>
                    @error('discount_reason') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label>Support Document <span style="color:var(--text-muted);font-weight:normal;font-size:12px;">(replace existing)</span></label>
                    <input type="file" name="discount_file" class="form-control @error('discount_file') is-invalid @enderror" accept=".pdf,.jpg,.jpeg,.png,.webp">
                    @error('discount_file') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            <button type="submit" class="btn-teal mt-3" style="width:100%;padding:11px;justify-content:center;">
                <i class="fa-solid fa-check"></i> Save Changes
            </button>
            <a href="{{ route('enrollments.show', $enrollment->id) }}" class="btn-outline-teal mt-2" style="width:100%;padding:11px;justify-content:center;display:inline-flex;">Cancel</a>
        </div>
    </div>
</div>
</form>
@endsection

@push('scripts')
@php
    $editScheduleSlots = $enrollment->schedules
        ->unique(fn ($s) => strtolower(trim((string) $s->day)) . '|' . trim((string) $s->time_slot))
        ->values()
        ->map(fn ($s) => ['day' => $s->day, 'time_slot' => $s->time_slot])
        ->all();
@endphp
@include('enrollments.partials.form-scripts', [
    'isEdit' => true,
    'enrollment' => $enrollment,
    'excludeEnrollmentId' => $enrollment->id,
    'initialSchedules' => $editScheduleSlots,
    'initialServiceId' => $enrollment->service_id,
    'therapistNameInit' => $enrollment->therapist?->full_name,
    'enrollmentPricing' => $enrollmentPricing ?? [],
])
@endpush
