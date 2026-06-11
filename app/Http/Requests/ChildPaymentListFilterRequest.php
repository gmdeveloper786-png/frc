<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChildPaymentListFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isChild() ?? false;
    }

    public function rules(): array
    {
        $childId = (int) $this->user()->id;

        return [
            'search'              => ['nullable', 'string', 'max:100'],
            'verification_status' => ['nullable', Rule::in(['pending_verification', 'paid', 'rejected', 'cancelled', 'refunded'])],
            'enrollment_id'       => [
                'nullable',
                'integer',
                Rule::exists('enrollments', 'id')->where(fn ($q) => $q->where('child_id', $childId)),
            ],
            'payment_method'      => ['nullable', Rule::in(['cash', 'bank_transfer', 'easypaisa', 'jazzcash', 'card', 'other'])],
            'date_from'           => ['nullable', 'date'],
            'date_to'             => ['nullable', 'date', 'after_or_equal:date_from'],
        ];
    }
}
