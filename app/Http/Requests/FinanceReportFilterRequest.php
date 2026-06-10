<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FinanceReportFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_id'                 => ['nullable', 'integer', 'exists:branches,id'],
            'therapist_id'              => ['nullable', 'integer', 'exists:users,id'],
            'service_id'                => ['nullable', 'integer', 'exists:services,id'],
            'payment_method'            => ['nullable', 'in:cash,bank_transfer,easypaisa,jazzcash,card,other'],
            'date_from'                 => ['nullable', 'date'],
            'date_to'                   => ['nullable', 'date', 'after_or_equal:date_from'],
            'enrollment_payment_status' => ['nullable', 'in:unpaid,partial_paid,fully_paid,overdue'],
            'verification_status'       => ['nullable', 'in:pending_verification,paid,rejected,cancelled,refunded'],
            'child_search'              => ['nullable', 'string', 'max:100'],
            'gr_number'                 => ['nullable', 'string', 'max:50'],
            'per_page'                  => ['nullable', 'integer', 'min:5', 'max:100'],
        ];
    }
}
