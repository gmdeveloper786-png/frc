@php
    $rawBlockIndex = $blockIndex ?? 0;
    $isTemplate = $rawBlockIndex === '__INDEX__';
    $blockIndex = $isTemplate ? '__INDEX__' : (int) $rawBlockIndex;
    $isPrimary = ! $isTemplate && $blockIndex === 0;
    $showChildren = $showChildren ?? $isPrimary;
    $showRemove = $showRemove ?? ! $isPrimary;
    $blockLabel = $isPrimary ? 'Enrollment' : ($isTemplate ? 'Enrollment' : 'Enrollment ' . ($blockIndex + 1));

    $fieldName = function (string $field) use ($isPrimary, $blockIndex, $isTemplate): string {
        if ($isPrimary) {
            return $field;
        }

        $index = $isTemplate ? '__INDEX__' : $blockIndex;

        return "extra_enrollments[{$index}][{$field}]";
    };

    $fieldOld = function (string $field, mixed $default = null) use ($isPrimary, $blockIndex, $isTemplate): mixed {
        if ($isPrimary) {
            return old($field, $default);
        }
        if ($isTemplate) {
            return $default;
        }

        return old("extra_enrollments.{$blockIndex}.{$field}", $default);
    };

    $fieldError = function (string $field) use ($isPrimary, $blockIndex, $isTemplate): string {
        if ($isPrimary) {
            return $field;
        }
        if ($isTemplate) {
            return "extra_enrollments.__INDEX__.{$field}";
        }

        return "extra_enrollments.{$blockIndex}.{$field}";
    };

    $scheduleOld = $isPrimary ? old('schedules', []) : ($isTemplate ? [] : old("extra_enrollments.{$blockIndex}.schedules", []));
    if (! is_array($scheduleOld)) {
        $scheduleOld = [];
    }
    $blockHasErrors = ! $isTemplate && (
        $errors->has($fieldError('schedules'))
        || $errors->has($fieldError('schedule_start_date'))
        || $errors->has($fieldError('therapist_id'))
        || $errors->has($fieldError('branch_id'))
        || $errors->has($fieldError('service_id'))
    );
@endphp

<div class="enrollment-block {{ $isPrimary ? '' : 'enrollment-block-extra' }}{{ $blockHasErrors ? ' enrollment-block-has-error' : '' }}"
    data-enrollment-block
    data-block-index="{{ $blockIndex }}"
    data-schedule-prefix="{{ $isPrimary ? 'schedules' : ($isTemplate ? 'extra_enrollments[__INDEX__][schedules]' : $fieldName('schedules')) }}"
    @if(! $isTemplate && $fieldOld('therapist_id')) data-old-therapist-id="{{ $fieldOld('therapist_id') }}" @endif
    @if(! $isPrimary) data-extra-block @endif>

    @if(! $isPrimary)
        <div class="enrollment-block-header">
            <div class="form-section-title mb-0">
                <i class="fa-solid fa-file-medical" style="color:var(--teal);"></i> {{ $blockLabel }}
            </div>
            <button type="button" class="btn-outline-danger btn-sm" data-remove-enrollment-block title="Remove this enrollment">
                <i class="fa-solid fa-trash"></i> Remove
            </button>
        </div>
        <div class="enrollment-locked-children mb-3" data-locked-children-display>
            <label class="d-block mb-2">Children</label>
            <div class="enrollment-locked-children-list text-muted small" style="background:var(--bg-light);border:1px solid var(--border-soft);border-radius:10px;padding:12px 14px;">
                Same children as the first enrollment — select children above.
            </div>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-12 col-lg-8">
            @if($showChildren)
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
                        @include('enrollments.partials.enrollment-block-fields', [
                            'blockIndex' => $blockIndex,
                            'isPrimary' => $isPrimary,
                            'fieldName' => $fieldName,
                            'fieldOld' => $fieldOld,
                            'fieldError' => $fieldError,
                            'branches' => $branches,
                            'services' => $services,
                        ])
                    </div>
                </div>
            @else
                <div class="form-section">
                    <div class="form-section-title"><i class="fa-solid fa-user-doctor" style="color:var(--teal);"></i> Therapist & Service</div>
                    <div class="row g-3">
                        @include('enrollments.partials.enrollment-block-fields', [
                            'blockIndex' => $blockIndex,
                            'isPrimary' => $isPrimary,
                            'fieldName' => $fieldName,
                            'fieldOld' => $fieldOld,
                            'fieldError' => $fieldError,
                            'branches' => $branches,
                            'services' => $services,
                        ])
                    </div>
                </div>
            @endif

            <div class="form-section">
                <div class="form-section-title"><i class="fa-solid fa-calendar-days" style="color:var(--teal);"></i> Session Schedule</div>
                @if(! $isPrimary && ! $isTemplate)
                    <div class="alert-frc warning mb-3 py-2 px-3" style="font-size:13px;">
                        <i class="fa-solid fa-circle-info"></i>
                        <span>Choose <strong>Day</strong> and <strong>Time Slot</strong> below after therapist is selected.</span>
                    </div>
                @endif
                <div class="row g-3 mb-3">
                    <div class="col-12">
                        <label>First session starts on <span style="color:var(--danger)">*</span></label>
                        <input type="date"
                            name="{{ $fieldName('schedule_start_date') }}"
                            data-schedule-start-date
                            class="form-control @error($fieldError('schedule_start_date')) is-invalid @enderror"
                            value="{{ $fieldOld('schedule_start_date', now()->toDateString()) }}"
                            min="{{ now()->toDateString() }}" required>
                        @error($fieldError('schedule_start_date')) <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <p class="text-muted small mb-0 mt-1" data-first-session-hint>Pick a start date — the matching weekday is added to the schedule when the therapist works that day; then choose a time slot.</p>
                    </div>
                </div>
                <div data-schedule-rows>
                    @php $scheduleRows = count($scheduleOld) > 0 ? $scheduleOld : [[]]; @endphp
                    @foreach($scheduleRows as $sIdx => $scheduleRow)
                        <div class="schedule-row" data-schedule-row>
                            <div>
                                <label>Day <span style="color:var(--danger)">*</span></label>
                                <select name="{{ $fieldName('schedules') }}[{{ $sIdx }}][day]" class="form-control" data-day-select>
                                    <option value="">Select Day</option>
                                    @if(! empty($scheduleRow['day']))
                                        <option value="{{ $scheduleRow['day'] }}" selected>{{ $scheduleRow['day'] }}</option>
                                    @endif
                                </select>
                            </div>
                            <div>
                                <label>Time Slot <span style="color:var(--danger)">*</span></label>
                                <select name="{{ $fieldName('schedules') }}[{{ $sIdx }}][time_slot]" class="form-control" data-slot-select>
                                    <option value="">Select Day First</option>
                                    @if(! empty($scheduleRow['time_slot']))
                                        <option value="{{ $scheduleRow['time_slot'] }}" selected>{{ $scheduleRow['time_slot'] }}</option>
                                    @endif
                                </select>
                            </div>
                            <div style="padding-bottom:2px;">
                                <button type="button" data-remove-row style="background:var(--danger);color:#fff;border:none;border-radius:8px;padding:8px 12px;cursor:pointer;margin-top:20px;" title="Remove" aria-label="Remove"><i class="fa-solid fa-xmark"></i></button>
                            </div>
                        </div>
                    @endforeach
                </div>
                @error($fieldError('schedules')) <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                <button type="button" class="btn-outline-teal mt-2" data-add-schedule-row>
                    <i class="fa-solid fa-plus"></i> Add Another Day/Slot
                </button>
            </div>

            <div class="form-section">
                <div class="form-section-title"><i class="fa-solid fa-clock" style="color:var(--teal);"></i> Duration</div>
                <div class="row g-3">
                    <div class="col-12">
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                            <input type="checkbox" name="{{ $fieldName('repeat_weekly') }}" value="1" data-repeat-weekly
                                {{ $fieldOld('repeat_weekly') ? 'checked' : '' }}
                                style="accent-color:var(--teal);">
                            Repeat Weekly (auto-generate sessions)
                        </label>
                    </div>
                    <div data-duration-section style="display:none;" class="col-12">
                        <div class="row g-3">
                            <div class="col-6">
                                <label>Duration Value</label>
                                <input type="number" name="{{ $fieldName('duration_value') }}" data-duration-value
                                    value="{{ $fieldOld('duration_value') }}" class="form-control" min="1" placeholder="e.g. 3">
                            </div>
                            <div class="col-6">
                                <label>Duration Unit</label>
                                <select name="{{ $fieldName('duration_unit') }}" data-duration-unit class="form-control">
                                    <option value="weekly" {{ $fieldOld('duration_unit', 'weekly') === 'weekly' ? 'selected' : '' }}>Weekly</option>
                                    <option value="monthly" {{ $fieldOld('duration_unit') === 'monthly' ? 'selected' : '' }}>Monthly</option>
                                    <option value="yearly" {{ $fieldOld('duration_unit') === 'yearly' ? 'selected' : '' }}>Yearly</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="form-section enrollment-form-fee-panel" @if($isPrimary) style="position:sticky;top:80px;" @endif>
                <div class="form-section-title"><i class="fa-solid fa-calculator" style="color:var(--teal);"></i> Fee Calculation</div>
                <div class="mb-3">
                    <label>Price Per Session (PKR) <span style="color:var(--danger)">*</span></label>
                    <input type="number" name="{{ $fieldName('price_per_session') }}" data-price-per-session
                        value="{{ $fieldOld('price_per_session', 0) }}"
                        class="form-control" min="0" step="1" readonly tabindex="-1"
                        style="background:var(--bg-light);cursor:not-allowed;">
                    <small data-session-price-hint class="text-muted d-block mt-1" style="font-size:12px;"></small>
                </div>
                <div class="mb-3">
                    <label>Discount % (0–100)</label>
                    <input type="number" name="{{ $fieldName('discount_percentage') }}" data-discount-pct
                        value="{{ $fieldOld('discount_percentage', 0) }}"
                        class="form-control" min="0" max="100" step="1">
                </div>
                @include('enrollments.partials.zakat-eligibility-field', [
                    'selected' => $fieldOld('zakat_eligibility'),
                    'namePrefix' => $isPrimary ? null : ($isTemplate ? 'extra_enrollments[__INDEX__]' : "extra_enrollments[{$blockIndex}]"),
                ])
                <div style="background:var(--bg-light);border-radius:12px;padding:16px;margin-bottom:16px;">
                    <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:8px;">
                        <span style="color:var(--text-muted);">Sessions (auto):</span>
                        <strong data-calc-sessions>0</strong>
                    </div>
                    <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:8px;">
                        <span style="color:var(--text-muted);">Subtotal:</span>
                        <strong>PKR <span data-calc-subtotal>0</span></strong>
                    </div>
                    <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:8px;">
                        <span style="color:var(--text-muted);">Discount:</span>
                        <strong style="color:var(--danger);">- PKR <span data-calc-discount>0</span></strong>
                    </div>
                    <hr style="border-color:var(--border-soft);">
                    <div style="display:flex;justify-content:space-between;font-size:16px;font-family:'Poppins',sans-serif;font-weight:700;">
                        <span>Total:</span>
                        <span style="color:var(--teal);">PKR <span data-calc-total>0</span></span>
                    </div>
                </div>

                <div data-discount-section style="display:none;">
                    <div class="alert-frc warning mb-3">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <div><strong>High Discount (&gt;{{ $frc['high_discount_threshold'] }}%)</strong> requires approval. Provide a reason (supporting document optional).</div>
                    </div>
                    <div class="mb-3">
                        <label>Discount Reason <span style="color:var(--danger)">*</span></label>
                        <textarea name="{{ $fieldName('discount_reason') }}" class="form-control @error($fieldError('discount_reason')) is-invalid @enderror" rows="3" placeholder="Why is this discount being given?">{{ $fieldOld('discount_reason') }}</textarea>
                        @error($fieldError('discount_reason')) <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label>Support Document <span style="color:var(--text-muted);font-weight:normal;font-size:12px;">(optional)</span></label>
                        <input type="file" name="{{ $fieldName('discount_file') }}" class="form-control @error($fieldError('discount_file')) is-invalid @enderror" accept=".pdf,.jpg,.jpeg,.png,.webp">
                        @error($fieldError('discount_file')) <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
