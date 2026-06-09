<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateChildOwnProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isChild() ?? false;
    }

    public function rules(): array
    {
        return [
            'full_name'       => ['required', 'string', 'max:255'],
            'father_name'     => ['nullable', 'string', 'max:255'],
            'phone_number'    => ['nullable', 'string', 'max:20'],
            'whatsapp_number' => ['nullable', 'string', 'max:20'],
            'address'         => ['nullable', 'string', 'max:1000'],
        ];
    }
}
