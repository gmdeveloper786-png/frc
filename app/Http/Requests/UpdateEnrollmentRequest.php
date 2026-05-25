<?php

namespace App\Http\Requests;

use App\Models\Enrollment;
use Carbon\Carbon;
use Illuminate\Validation\Rule;

class UpdateEnrollmentRequest extends StoreEnrollmentRequest
{
    public function rules(): array
    {
        $rules = array_merge(parent::rules(), [
            'status' => $this->statusRules(),
        ]);

        $rules['schedule_start_date'] = $this->scheduleStartDateRules();

        return $rules;
    }

    /**
     * Allow keeping an existing past start date; new/changed dates must be today or later.
     *
     * @return list<string|object>
     */
    private function scheduleStartDateRules(): array
    {
        $existing = $this->existingScheduleStartDate();

        if ($existing !== null && (string) $this->input('schedule_start_date') === $existing) {
            return ['required', 'date'];
        }

        return ['required', 'date', 'after_or_equal:today'];
    }

    private function existingScheduleStartDate(): ?string
    {
        $id = (int) $this->route('id');
        if ($id < 1) {
            return null;
        }

        $value = Enrollment::query()->whereKey($id)->value('schedule_start_date');

        return $value ? Carbon::parse($value)->toDateString() : null;
    }

    /**
     * @return list<string|object>
     */
    private function statusRules(): array
    {
        $user = $this->user();
        $id = (int) $this->route('id');

        $current = $id > 0
            ? (string) (Enrollment::query()->whereKey($id)->value('status') ?? '')
            : '';

        if ($user?->isSuperAdmin()) {
            $allowed = ['draft', 'pending_super_admin_approval', 'rejected', 'active', 'completed', 'cancelled'];
            if ($current === 'approved') {
                $allowed[] = 'approved';
            }

            return ['nullable', Rule::in($allowed)];
        }

        if ($current === 'pending_super_admin_approval') {
            return ['required', Rule::in(['pending_super_admin_approval'])];
        }

        $allowed = ['draft', 'rejected', 'active', 'completed', 'cancelled'];
        if ($current === 'approved') {
            $allowed[] = 'approved';
        }

        return ['nullable', Rule::in($allowed)];
    }
}
