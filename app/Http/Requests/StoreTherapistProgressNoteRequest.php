<?php

namespace App\Http\Requests;

use App\Models\EnrollmentSchedule;
use App\Models\ProgressNote;
use App\Services\TherapistPortalService;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTherapistProgressNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isTherapist() ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('occurrence_pick')) {
            $raw = (string) $this->input('occurrence_pick');
            $parts = explode('|', $raw, 2);
            if (count($parts) === 2 && ctype_digit($parts[0])) {
                $this->merge([
                    'enrollment_schedule_id' => (int) $parts[0],
                    'session_date'             => $parts[1],
                ]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'occurrence_pick'        => ['nullable', 'string', 'max:80'],
            'child_id'               => ['required', 'exists:users,id'],
            'enrollment_id'          => ['nullable', 'exists:enrollments,id'],
            'enrollment_schedule_id' => ['required', 'integer', 'exists:enrollment_schedules,id'],
            'service_id'             => ['nullable', 'exists:services,id'],
            'session_date'           => ['required', 'date'],
            'therapy_goal'           => ['nullable', 'string', 'max:1000'],
            'notes'                  => ['required', 'string', 'max:8000'],
            'child_response'         => ['nullable', 'string', 'max:4000'],
            'progress_level'         => ['required', Rule::in(ProgressNote::PROGRESS_LEVELS)],
            'parent_instructions'    => ['nullable', 'string', 'max:4000'],
            'next_plan'              => ['nullable', 'string', 'max:4000'],
            'status'                 => ['required', Rule::in(ProgressNote::STATUSES)],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $user = $this->user();
            if ($user === null) {
                return;
            }

            $tid = (int) $user->id;
            $sid = (int) $this->input('enrollment_schedule_id');

            try {
                $sessionDateIso = Carbon::parse((string) $this->input('session_date'))->toDateString();
            } catch (\Throwable) {
                $validator->errors()->add('session_date', 'Invalid session date.');

                return;
            }

            /** @var TherapistPortalService $portal */
            $portal = app(TherapistPortalService::class);

            if (! $portal->occurrenceBelongsToTherapistCompletedSchedule($tid, $sid, $sessionDateIso)) {
                $validator->errors()->add('enrollment_schedule_id', 'Select a completed session assigned to you.');
            }

            $schedule = EnrollmentSchedule::query()->with('enrollment')->find($sid);
            if ($schedule && $schedule->enrollment && (int) $schedule->enrollment->child_id !== (int) $this->input('child_id')) {
                $validator->errors()->add('child_id', 'Child must match the linked session.');
            }
        });
    }
}
