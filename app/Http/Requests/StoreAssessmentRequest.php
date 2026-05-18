<?php

namespace App\Http\Requests;

use App\Models\Assessment;
use App\Models\Role;
use App\Models\User;
use App\Services\TherapistService;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreAssessmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('manage_assessments') ?? false;
    }

    protected function prepareForValidation(): void
    {
        foreach (['service_ids', 'child_ids'] as $key) {
            $raw = $this->input($key);
            if ($raw !== null && ! is_array($raw)) {
                $this->merge([$key => [$raw]]);
            }
        }

        if ($this->input('child_ids') === null) {
            $this->merge(['child_ids' => []]);
        }
    }

    public function rules(): array
    {
        return [
            'date'          => ['required', 'date', $this->notPastAssessmentDateRule()],
            'time'          => ['required', 'date_format:H:i,H:i:s,h:i A,g:i A'],
            'branch_id'     => ['required', 'exists:branches,id'],
            'therapist_id'  => [
                Rule::requiredIf(fn () => $this->input('status') === 'publish'),
                'nullable',
                'integer',
                'exists:users,id',
            ],
            'service_ids'   => ['sometimes', 'nullable', 'array'],
            'service_ids.*' => ['integer', Rule::exists('services', 'id')->where('status', 'publish')],
            'child_ids'     => ['required_if:status,publish', 'array'],
            'child_ids.*'   => [
                'integer',
                Rule::exists('users', 'id')->where(function ($q): void {
                    $q->whereIn('status', ['approved', 'active'])
                        ->whereIn(
                            'role_id',
                            DB::table('roles')->where('name', Role::CHILD)->select('id'),
                        );
                }),
            ],
            'status'        => ['required', Rule::in(['draft', 'publish'])],
        ];
    }

    public function messages(): array
    {
        return [
            'date' => 'Assessment date cannot be in the past.',
        ];
    }

    /**
     * @return \Closure(string, mixed, \Closure): void
     */
    private function notPastAssessmentDateRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            if (! is_string($value) || $value === '') {
                return;
            }

            $selected = Carbon::parse($value)->startOfDay();
            $today    = now()->startOfDay();

            if ($selected->gte($today)) {
                return;
            }

            $assessment = $this->route('assessment');
            if ($assessment instanceof Assessment
                && $assessment->date->format('Y-m-d') === $selected->format('Y-m-d')) {
                return;
            }

            $fail('Assessment date cannot be in the past.');
        };
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $status = $this->input('status');

            if ($status === 'publish') {
                $childIds = array_filter(array_map('intval', (array) $this->input('child_ids', [])));
                if ($childIds === []) {
                    $validator->errors()->add('child_ids', 'At least one approved child is required when publishing.');
                }
            }

            $therapistId = (int) ($this->input('therapist_id') ?: 0);
            $branchId    = (int) $this->input('branch_id', 0);
            $serviceIds  = array_values(array_unique(array_filter(array_map('intval', (array) $this->input('service_ids', [])))));

            if ($status !== 'publish' || $therapistId < 1) {
                return;
            }

            $therapist = User::with(['therapistProfile', 'therapistServices'])->find($therapistId);
            if (! $therapist || ! $therapist->isTherapist()) {
                $validator->errors()->add('therapist_id', 'Invalid therapist selected.');

                return;
            }

            if ($branchId > 0 && ! app(TherapistService::class)->therapistQualifiesForFilters($therapist, $branchId, $serviceIds, 'any')) {
                $validator->errors()->add('therapist_id', 'Selected therapist does not belong to the selected branch.');
            }
        });
    }
}
