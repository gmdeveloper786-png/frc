<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CancelAssessmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user?->isSuperAdmin() && $user->hasPermission('manage_assessments');
    }

    public function rules(): array
    {
        return [
            'cancellation_reason' => ['required', 'string', 'max:65000'],
        ];
    }
}
