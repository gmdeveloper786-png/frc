<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NotificationIndexFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'tab'       => ['nullable', 'in:all,unread,read'],
            'search'    => ['nullable', 'string', 'max:100'],
            'type'      => ['nullable', 'string', 'max:80'],
            'module'    => ['nullable', 'string', 'max:80'],
            'date_from' => ['nullable', 'date'],
            'date_to'   => ['nullable', 'date', 'after_or_equal:date_from'],
            'per_page'  => ['nullable', 'integer', 'min:5', 'max:50'],
        ];
    }
}
