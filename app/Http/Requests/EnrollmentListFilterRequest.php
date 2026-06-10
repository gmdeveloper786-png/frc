<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EnrollmentListFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('manage_enrollments') ?? false;
    }

    public function rules(): array
    {
        return [
            'status'         => ['nullable', 'string', 'max:50'],
            'branch_id'      => ['nullable', 'integer', 'exists:branches,id'],
            'child_id'       => ['nullable', 'integer', 'exists:users,id'],
            'payment_status' => ['nullable', 'in:unpaid,partial_paid,fully_paid,overdue'],
        ];
    }
}
