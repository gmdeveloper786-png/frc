<?php

namespace App\Http\Requests;

use App\Models\Enrollment;
use Illuminate\Validation\Rule;

class UpdateEnrollmentRequest extends StoreEnrollmentRequest
{
    public function rules(): array
    {
        $rules = array_merge(parent::rules(), [
            'status' => $this->statusRules(),
        ]);
        $rules['schedule_start_date'] = ['required', 'date'];

        return $rules;
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
