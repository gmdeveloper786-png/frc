<?php

namespace App\Http\Requests;

use App\Models\Enrollment;
use App\Models\Role;
use App\Models\User;
use App\Support\UploadRules;
use App\Services\SettingService;
use App\Support\CitySessionPricing;
use App\Support\StaffBranchScope;
use App\Services\TherapistService;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Enrollment::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $raw = $this->input('child_ids');
        if ($raw !== null && ! is_array($raw)) {
            $this->merge(['child_ids' => [$raw]]);
        }

        if ($this->has('child_id') && ! $this->has('child_ids')) {
            $legacy = $this->input('child_id');
            if ($legacy !== null && $legacy !== '') {
                $this->merge(['child_ids' => [(int) $legacy]]);
            }
        }

        $user = $this->user();
        if ($user && ($locked = StaffBranchScope::lockedBranchId($user))) {
            $this->merge(['branch_id' => $locked]);
            $extras = $this->input('extra_enrollments', []);
            if (is_array($extras)) {
                foreach ($extras as $index => $extra) {
                    if (is_array($extra)) {
                        $extras[$index]['branch_id'] = $locked;
                    }
                }
                $this->merge(['extra_enrollments' => $extras]);
            }
        }

        $branchId = (int) $this->input('branch_id', 0);
        if ($branchId > 0) {
            $auto = app(CitySessionPricing::class)->priceForBranchId($branchId);
            if ($auto !== null) {
                $this->merge(['price_per_session' => $auto]);
            }
        }

        $extras = $this->input('extra_enrollments', []);
        if (is_array($extras)) {
            $pricing = app(CitySessionPricing::class);
            foreach ($extras as $index => $extra) {
                if (! is_array($extra)) {
                    unset($extras[$index]);
                    continue;
                }
                $extra['schedules'] = $this->normalizeSchedules($extra['schedules'] ?? null);
                if (! $this->isExtraEnrollmentActive($extra)) {
                    unset($extras[$index]);
                    continue;
                }
                $extraBranchId = (int) ($extra['branch_id'] ?? 0);
                if ($extraBranchId > 0) {
                    $auto = $pricing->priceForBranchId($extraBranchId);
                    if ($auto !== null) {
                        $extra['price_per_session'] = $auto;
                    }
                }
                $extras[$index] = $extra;
            }
            $this->merge(['extra_enrollments' => $extras]);
        }

        $primarySchedules = $this->normalizeSchedules($this->input('schedules'));
        $this->merge(['schedules' => $primarySchedules]);
    }

    /**
     * @return array<int, array{day: string, time_slot: string}>
     */
    private function normalizeSchedules(mixed $schedules): array
    {
        if (! is_array($schedules)) {
            return [];
        }

        $normalized = [];
        foreach ($schedules as $row) {
            if (! is_array($row)) {
                continue;
            }
            $day = trim((string) ($row['day'] ?? ''));
            $slot = trim((string) ($row['time_slot'] ?? ''));
            if ($day === '' || $slot === '') {
                continue;
            }
            $normalized[] = ['day' => $day, 'time_slot' => $slot];
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function isExtraEnrollmentActive(array $extra): bool
    {
        if ($this->normalizeSchedules($extra['schedules'] ?? null) !== []) {
            return true;
        }

        $branch = (int) ($extra['branch_id'] ?? 0);
        $service = (int) ($extra['service_id'] ?? 0);
        $therapist = (int) ($extra['therapist_id'] ?? 0);

        return $branch > 0 && $service > 0 && $therapist > 0;
    }

    public function rules(): array
    {
        $rules = array_merge(
            $this->enrollmentFieldRules('', (float) $this->input('discount_percentage', 0)),
            [
                'child_ids'   => ['required', 'array', 'min:1'],
                'child_ids.*' => [
                    'integer',
                    'distinct',
                    Rule::exists('users', 'id')->where(function ($q): void {
                        $q->whereIn('status', ['approved', 'active'])
                            ->whereIn(
                                'role_id',
                                DB::table('roles')->where('name', Role::CHILD)->select('id'),
                            );
                    }),
                ],
                'extra_enrollments' => ['nullable', 'array'],
            ],
        );

        foreach ((array) $this->input('extra_enrollments', []) as $index => $extra) {
            if (! is_array($extra) || ! $this->isExtraEnrollmentActive($extra)) {
                continue;
            }
            $discountPct = (float) ($extra['discount_percentage'] ?? 0);
            $rules = array_merge(
                $rules,
                $this->enrollmentFieldRules("extra_enrollments.{$index}.", $discountPct),
            );
        }

        return $rules;
    }

    /**
     * @return array<string, mixed>
     */
    private function enrollmentFieldRules(string $prefix, float $discountPct): array
    {
        $highDiscount = app(SettingService::class)->isHighDiscount($discountPct);
        $key = fn (string $field): string => $prefix.$field;

        return [
            $key('branch_id') => [
                'required',
                'integer',
                Rule::exists('branches', 'id')->where(fn ($q) => $q->where('status', 'publish')),
            ],
            $key('service_id') => ['required', 'integer', Rule::exists('services', 'id')->where('status', 'publish')],
            $key('therapist_id') => ['required', 'exists:users,id'],
            $key('price_per_session') => ['required', 'numeric', 'min:0'],
            $key('discount_percentage') => ['nullable', 'numeric', 'min:0', 'max:100'],
            $key('zakat_eligibility') => ['required', Rule::in(array_keys(Enrollment::zakatEligibilityOptions()))],
            $key('repeat_weekly') => ['nullable', 'boolean'],
            $key('schedule_start_date') => ['required', 'date', 'after_or_equal:today'],
            $key('duration_value') => ['nullable', 'required_if:'.$key('repeat_weekly').',true', 'integer', 'min:1'],
            $key('duration_unit') => ['nullable', 'required_if:'.$key('repeat_weekly').',true', 'in:weekly,monthly,yearly'],
            $key('schedules') => ['required', 'array', 'min:1'],
            $key('schedules.*.day') => ['required', 'string'],
            $key('schedules.*.time_slot') => ['required', 'string'],
            $key('discount_reason') => [$highDiscount ? 'required' : 'nullable', 'string', 'max:2000'],
            $key('discount_file') => UploadRules::document(required: false),
            $key('status') => ['nullable', 'in:draft,active'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->validateEnrollmentPayload($validator, $this->only([
                'branch_id',
                'service_id',
                'therapist_id',
                'schedules',
                'schedule_start_date',
            ]), '');

            foreach ((array) $this->input('extra_enrollments', []) as $index => $extra) {
                if (! is_array($extra)) {
                    continue;
                }
                $this->validateEnrollmentPayload($validator, $extra, "extra_enrollments.{$index}.");
            }

            $this->validateCrossEnrollmentScheduleConflicts($validator);
        });
    }

    private function validateCrossEnrollmentScheduleConflicts(Validator $validator): void
    {
        if ($validator->errors()->isNotEmpty()) {
            return;
        }

        $groups = [[
            'prefix'       => '',
            'therapist_id' => (int) $this->input('therapist_id', 0),
            'schedules'    => (array) $this->input('schedules', []),
        ]];

        foreach ((array) $this->input('extra_enrollments', []) as $index => $extra) {
            if (! is_array($extra)) {
                continue;
            }
            $groups[] = [
                'prefix'       => "extra_enrollments.{$index}.",
                'therapist_id' => (int) ($extra['therapist_id'] ?? 0),
                'schedules'    => (array) ($extra['schedules'] ?? []),
            ];
        }

        if (count($groups) < 2) {
            return;
        }

        $childSlotSeen = [];
        $therapistSlotSeen = [];

        foreach ($groups as $group) {
            $prefix = $group['prefix'];
            $schedulesKey = $prefix === '' ? 'schedules' : "{$prefix}schedules";
            $therapistId = $group['therapist_id'];

            foreach ($group['schedules'] as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $day = trim((string) ($row['day'] ?? ''));
                $slot = trim((string) ($row['time_slot'] ?? ''));
                if ($day === '' || $slot === '') {
                    continue;
                }

                $slotKey = strtolower($day).'|'.$slot;

                if (isset($childSlotSeen[$slotKey])) {
                    $validator->errors()->add(
                        $schedulesKey,
                        'This day and time is already used in another enrollment on this form. The child cannot be in two sessions at the same time.',
                    );
                } else {
                    $childSlotSeen[$slotKey] = true;
                }

                if ($therapistId > 0) {
                    $therapistKey = $therapistId.'|'.$slotKey;
                    if (isset($therapistSlotSeen[$therapistKey])) {
                        $validator->errors()->add(
                            $schedulesKey,
                            'This therapist is already scheduled at this day and time in another enrollment on this form.',
                        );
                    } else {
                        $therapistSlotSeen[$therapistKey] = true;
                    }
                }
            }
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function validateEnrollmentPayload(Validator $validator, array $data, string $prefix): void
    {
        $schedules = $data['schedules'] ?? [];
        if (! is_array($schedules)) {
            return;
        }

        $schedulesKey = $prefix === '' ? 'schedules' : "{$prefix}schedules";
        $seen = [];
        foreach ($schedules as $row) {
            $day = isset($row['day']) ? (string) $row['day'] : '';
            $slot = isset($row['time_slot']) ? (string) $row['time_slot'] : '';
            if ($day === '' || $slot === '') {
                continue;
            }
            $key = $day.'|'.$slot;
            if (isset($seen[$key])) {
                $validator->errors()->add(
                    $schedulesKey,
                    'Same day and time slot cannot be added more than once.'
                );

                return;
            }
            $seen[$key] = true;
        }

        $branchId = (int) ($data['branch_id'] ?? 0);
        $user = $this->user();
        if ($user && $branchId > 0) {
            try {
                StaffBranchScope::assertBranchAssignable($user, $branchId);
            } catch (\Throwable) {
                $validator->errors()->add(
                    $prefix === '' ? 'branch_id' : "{$prefix}branch_id",
                    'You cannot assign enrollments to this branch.',
                );
            }
        }

        if ($branchId > 0) {
            $cityPrice = app(CitySessionPricing::class)->priceForBranchId($branchId);
            if ($cityPrice === null) {
                $validator->errors()->add(
                    $prefix === '' ? 'branch_id' : "{$prefix}branch_id",
                    'No session price is configured for this branch\'s city. Set it under System Settings → City session pricing.',
                );
            }
        }

        $childIds = array_filter(array_map('intval', (array) $this->input('child_ids', [])));
        if ($childIds !== [] && $branchId > 0) {
            $allowedCount = User::children()
                ->approved()
                ->whereIn('id', $childIds)
                ->where('branch_id', $branchId)
                ->count();
            if ($allowedCount !== count($childIds)) {
                $validator->errors()->add(
                    'child_ids',
                    $prefix === ''
                        ? 'One or more selected children do not belong to this branch.'
                        : 'One or more selected children do not belong to the branch chosen in an additional enrollment.'
                );
            }
        }

        $therapistId = (int) ($data['therapist_id'] ?? 0);
        if ($therapistId < 1) {
            return;
        }

        $serviceId = (int) ($data['service_id'] ?? 0);
        $therapist = User::with(['therapistProfile', 'therapistServices'])->find($therapistId);
        if (! $therapist) {
            return;
        }

        if ($branchId > 0 && $serviceId > 0) {
            if (! app(TherapistService::class)->therapistQualifiesForFilters($therapist, $branchId, [$serviceId])) {
                $validator->errors()->add(
                    $prefix === '' ? 'therapist_id' : "{$prefix}therapist_id",
                    'Selected therapist is not available for this branch and service.'
                );

                return;
            }
        }

        $slotRows = app(TherapistService::class)->getAvailableSlots($therapist);
        foreach ($schedules as $row) {
            $chosen = isset($row['time_slot']) ? (string) $row['time_slot'] : '';
            if ($chosen === '') {
                continue;
            }
            foreach ($slotRows as $meta) {
                if (($meta['slot'] ?? '') !== $chosen) {
                    continue;
                }
                if (filter_var($meta['disabled'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                    $validator->errors()->add(
                        $schedulesKey,
                        'Therapist break time slots cannot be booked.'
                    );

                    return;
                }
            }
        }

        $this->validateScheduleStartAlignsWithSelectedDays(
            $validator,
            (string) ($data['schedule_start_date'] ?? ''),
            $schedules,
            (int) ($data['therapist_id'] ?? 0),
            $prefix,
        );
    }

    private function validateScheduleStartAlignsWithSelectedDays(
        Validator $validator,
        string $startYmd,
        array $schedules,
        int $therapistId,
        string $prefix = '',
    ): void {
        if ($validator->errors()->isNotEmpty()) {
            return;
        }

        if ($startYmd === '') {
            return;
        }

        try {
            $startDayName = Carbon::parse($startYmd)->format('l');
        } catch (\Throwable) {
            return;
        }

        $startDayKey = strtolower($startDayName);
        $scheduleStartKey = $prefix === '' ? 'schedule_start_date' : "{$prefix}schedule_start_date";
        $schedulesKey = $prefix === '' ? 'schedules' : "{$prefix}schedules";

        if ($therapistId > 0) {
            $therapist = User::with('therapistProfile')->find($therapistId);
            $workingDays = collect($therapist?->therapistProfile?->working_days ?? [])
                ->map(fn ($d) => strtolower(trim((string) $d)))
                ->filter(fn ($d) => $d !== '');

            if ($workingDays->isNotEmpty() && ! $workingDays->contains($startDayKey)) {
                $validator->errors()->add(
                    $scheduleStartKey,
                    "This therapist does not work on {$startDayName}s. Choose another start date or therapist."
                );

                return;
            }
        }

        $scheduleDays = collect($schedules)
            ->pluck('day')
            ->filter(fn ($day) => is_string($day) && trim($day) !== '')
            ->map(fn ($day) => strtolower(trim((string) $day)));

        if (! $scheduleDays->contains($startDayKey)) {
            $validator->errors()->add(
                $schedulesKey,
                "The schedule must include {$startDayName} because the first session starts on that day."
            );
        }
    }

    public function messages(): array
    {
        return [
            'child_ids.required' => 'Select at least one child.',
            'child_ids.min' => 'Select at least one child.',
            'child_ids.*.distinct' => 'Each child can only be selected once.',
            'discount_reason.required' => 'Discount reason is required when discount exceeds the configured high-discount threshold.',
            'zakat_eligibility.required' => 'Please select a zakat eligibility option.',
            'zakat_eligibility.in' => 'Please select a valid zakat eligibility option.',
            'schedule_start_date.required' => 'Please choose when the first session should start.',
            'schedule_start_date.after_or_equal' => 'First session date cannot be in the past.',
            'schedules.required' => 'Please add at least one session day and time slot.',
            'schedules.min' => 'Please add at least one session day and time slot.',
            'extra_enrollments.*.schedules.required' => 'Please add a session day and time slot for the additional enrollment.',
            'extra_enrollments.*.schedules.min' => 'Please add a session day and time slot for the additional enrollment.',
            'extra_enrollments.*.schedules.*.day.required' => 'Please select a session day for the additional enrollment.',
            'extra_enrollments.*.schedules.*.time_slot.required' => 'Please select a time slot for the additional enrollment.',
        ];
    }
}
