<?php

namespace App\Http\Requests;

use App\Support\Money;
use App\Support\UploadRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreChildPaymentSlipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isChild() ?? false;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('amount')) {
            return;
        }

        $digits = preg_replace('/\D/', '', (string) $this->input('amount'));

        $this->merge([
            'amount' => $digits !== '' ? (int) $digits : null,
        ]);
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
            'amount'                => ['required', 'integer', 'min:1'],
            'payment_method'        => ['required', 'in:bank_transfer,easypaisa,jazzcash,card,other'],
            'transaction_reference' => ['nullable', 'string', 'max:255'],
            'payment_date'          => ['required', 'date', 'before_or_equal:today'],
            'payment_slip'          => UploadRules::document(),
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

            if ($enrollment->outstandingForSlipUpload() <= 0) {
                $pending = $enrollment->sumPendingVerificationAmount();
                $message = $pending > 0
                    ? 'You already submitted '.frc_pkr($pending).' for verification on this programme. Please wait for finance to verify before uploading another slip.'
                    : 'Your fee is fully paid for this programme. No payment slip is required.';

                $validator->errors()->add('enrollment_id', $message);

                return;
            }

            $amt = Money::round($this->input('amount'));
            $max = $enrollment->outstandingForSlipUpload();
            if ($amt > $max) {
                $validator->errors()->add(
                    'amount',
                    'Amount cannot be greater than your remaining balance ('.frc_pkr($max).').'
                );
            }
        });
    }
}
