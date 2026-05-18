<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectChildRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('approve_children') ?? false;
    }

    public function rules(): array
    {
        return [
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
