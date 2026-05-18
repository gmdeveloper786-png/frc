<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Services\SettingService;
use App\Services\TherapistService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('manage_enrollments') ?? false;
    }

    public function rules(): array
    {
        $discountPct = (float) $this->input('discount_percentage', 0);
        $highDiscount = app(SettingService::class)->isHighDiscount($discountPct);

        return [
            'child_id'           => ['required', 'exists:users,id'],
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
        });
    }

    public function messages(): array
    {
        return [
            'discount_reason.required' => 'Discount reason is required when discount exceeds the configured high-discount threshold.',
            'schedule_start_date.required' => 'Please choose when the first session should start.',
            'schedule_start_date.after_or_equal' => 'First session date cannot be in the past.',
        ];
    }
}
