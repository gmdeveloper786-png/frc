<?php

namespace App\Http\Requests;

use App\Models\Role;
use App\Models\User;
use App\Services\SettingService;
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
        return $this->user()?->hasPermission('manage_enrollments') ?? false;
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
    }

    public function rules(): array
    {
        $discountPct = (float) $this->input('discount_percentage', 0);
        $highDiscount = app(SettingService::class)->isHighDiscount($discountPct);

        return [
            'child_ids'          => ['required', 'array', 'min:1'],
            'child_ids.*'        => [
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
            'branch_id'          => ['required', 'exists:branches,id'],
            'service_id'         => ['required', 'integer', Rule::exists('services', 'id')->where('status', 'publish')],
            'therapist_id'       => ['required', 'exists:users,id'],
            'price_per_session'  => ['required', 'numeric', 'min:0'],
            'discount_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'repeat_weekly'        => ['nullable', 'boolean'],
            'schedule_start_date'  => ['required', 'date', 'after_or_equal:today'],
            'duration_value'       => ['nullable', 'required_if:repeat_weekly,true', 'integer', 'min:1'],
            'duration_unit'      => ['nullable', 'required_if:repeat_weekly,true', 'in:weekly,monthly,yearly'],
            'schedules'          => ['required', 'array', 'min:1'],
            'schedules.*.day'    => ['required', 'string'],
            'schedules.*.time_slot' => ['required', 'string'],
            'discount_reason'    => [$highDiscount ? 'required' : 'nullable', 'string', 'max:2000'],
            'discount_file'      => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:2048'],
            'status'             => ['nullable', 'in:draft,active'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $schedules = $this->input('schedules', []);
            if (! is_array($schedules)) {
                return;
            }
            $seen = [];
            foreach ($schedules as $index => $row) {
                $day = isset($row['day']) ? (string) $row['day'] : '';
                $slot = isset($row['time_slot']) ? (string) $row['time_slot'] : '';
                if ($day === '' || $slot === '') {
                    continue;
                }
                $key = $day.'|'.$slot;
                if (isset($seen[$key])) {
                    $validator->errors()->add(
                        'schedules',
                        'Same day and time slot cannot be added more than once.'
                    );

                    return;
                }
                $seen[$key] = true;
            }

            $therapistId = (int) $this->input('therapist_id', 0);
            if ($therapistId < 1) {
                return;
            }

            $branchId  = (int) $this->input('branch_id', 0);
            $serviceId = (int) $this->input('service_id', 0);

            $therapist = User::with(['therapistProfile', 'therapistServices'])->find($therapistId);
            if (! $therapist) {
                return;
            }

            if ($branchId > 0 && $serviceId > 0) {
                if (! app(TherapistService::class)->therapistQualifiesForFilters($therapist, $branchId, [$serviceId])) {
                    $validator->errors()->add(
                        'therapist_id',
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
                            'schedules',
                            'Therapist break time slots cannot be booked.'
                        );

                        return;
                    }
                }
            }

            $this->validateScheduleStartAlignsWithSelectedDays($validator);
        });
    }

    private function validateScheduleStartAlignsWithSelectedDays(Validator $validator): void
    {
        if ($validator->errors()->isNotEmpty()) {
            return;
        }

        $startYmd = $this->input('schedule_start_date');
        if (! is_string($startYmd) || $startYmd === '') {
            return;
        }

        try {
            $startDayName = Carbon::parse($startYmd)->format('l');
        } catch (\Throwable) {
            return;
        }

        $startDayKey = strtolower($startDayName);

        $therapistId = (int) $this->input('therapist_id', 0);
        if ($therapistId > 0) {
            $therapist = User::with('therapistProfile')->find($therapistId);
            $workingDays = collect($therapist?->therapistProfile?->working_days ?? [])
                ->map(fn ($d) => strtolower(trim((string) $d)))
                ->filter(fn ($d) => $d !== '');

            if ($workingDays->isNotEmpty() && ! $workingDays->contains($startDayKey)) {
                $validator->errors()->add(
                    'schedule_start_date',
                    "This therapist does not work on {$startDayName}s. Choose another start date or therapist."
                );

                return;
            }
        }

        $schedules = $this->input('schedules', []);
        if (! is_array($schedules)) {
            return;
        }

        $scheduleDays = collect($schedules)
            ->pluck('day')
            ->filter(fn ($day) => is_string($day) && trim($day) !== '')
            ->map(fn ($day) => strtolower(trim((string) $day)));

        if (! $scheduleDays->contains($startDayKey)) {
            $validator->errors()->add(
                'schedules',
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
            'schedule_start_date.required' => 'Please choose when the first session should start.',
            'schedule_start_date.after_or_equal' => 'First session date cannot be in the past.',
        ];
    }
}
