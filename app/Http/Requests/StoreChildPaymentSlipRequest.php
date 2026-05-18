<?php

namespace App\Http\Requests;

use App\Support\Money;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreChildPaymentSlipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isChild() ?? false;
    }

    public function rules(): array
    {
        return [
            'enrollment_id'         => [
                'required',
                Rule::exists('enrollments', 'id')->where(fn ($q) => $q
                    ->where('child_id', auth()->id())
                    ->whereIn('status', ['approved', 'active'])),
            ],
            'amount'                => ['required', 'numeric', 'min:0.01'],
            'payment_method'        => ['required', 'in:bank_transfer,easypaisa,jazzcash,card,other'],
            'transaction_reference' => ['nullable', 'string', 'max:255'],
            'payment_date'          => ['required', 'date', 'before_or_equal:today'],
            'payment_slip'          => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:2048'],
            'notes'                 => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $enrollment = \App\Models\Enrollment::find($this->input('enrollment_id'));
            if (! $enrollment) {
                return;
            }

            if ($enrollment->outstandingAmount() <= 0) {
                $validator->errors()->add(
                    'enrollment_id',
                    'Your fee is fully paid for this programme. No payment slip is required.'
                );

                return;
            }

            $amt = Money::round($this->input('amount'));
            $max = $enrollment->outstandingAmount();
            if ($amt > $max) {
                $validator->errors()->add(
                    'amount',
                    'Amount cannot be greater than your remaining balance (PKR '.number_format($max, 2).').'
                );
            }
        });
    }
}
