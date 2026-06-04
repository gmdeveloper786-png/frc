<?php

namespace App\Http\Requests;

use App\Models\Enrollment;
use App\Support\StaffBranchScope;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class StoreManualPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('manage_payments') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $amountDigits = preg_replace('/\D/', '', (string) $this->input('amount'));

        $this->merge([
            'payment_method' => 'cash',
            'amount' => $amountDigits !== '' ? (int) $amountDigits : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'enrollment_id'  => ['required', 'exists:enrollments,id', $this->enrollmentInStaffBranch()],
            'amount'         => ['required', 'integer', 'min:1', $this->amountWithinRemaining()],
            'payment_method' => ['required', 'in:cash'],
            'payment_date'   => ['required', 'date'],
            'notes'          => ['nullable', 'string', 'max:1000'],
        ];
    }

    private function enrollmentInStaffBranch(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $user = $this->user();
            if (! $user) {
                return;
            }

            $locked = StaffBranchScope::lockedBranchId($user);
            if ($locked === null) {
                return;
            }

            $enrollment = Enrollment::query()->find($value);
            if (! $enrollment) {
                return;
            }

            if ((int) $enrollment->branch_id !== $locked) {
                $fail('This enrollment belongs to another branch.');
            }
        };
    }

    private function amountWithinRemaining(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $enrollmentId = $this->input('enrollment_id');
            if (! $enrollmentId) {
                return;
            }

            $enrollment = Enrollment::query()->find($enrollmentId);
            if (! $enrollment) {
                return;
            }

            $remaining = $enrollment->outstandingAmount();
            if ($remaining <= 0) {
                $fail('This enrollment is already fully paid.');

                return;
            }

            if (round((float) $value, 2) > $remaining) {
                $fail('The amount cannot exceed the remaining balance ('.frc_pkr($remaining).').');
            }
        };
    }
}
