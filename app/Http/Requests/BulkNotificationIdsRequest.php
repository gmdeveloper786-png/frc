<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkNotificationIdsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'ids'   => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['integer', 'min:1'],
        ];
    }
}
