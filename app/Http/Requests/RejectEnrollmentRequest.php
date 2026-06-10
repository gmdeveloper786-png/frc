<?php

namespace App\Http\Requests;

use App\Models\Enrollment;
use Illuminate\Foundation\Http\FormRequest;

class RejectEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $enrollment = Enrollment::query()->find($this->route('id'));

        return $enrollment instanceof Enrollment
            && ($this->user()?->can('reject', $enrollment) ?? false);
    }

    public function rules(): array
    {
        return [
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
