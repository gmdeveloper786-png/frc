<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDisabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('manage_disabilities') ?? false;
    }

    public function rules(): array
    {
        /** @var \App\Models\Disability|null $disability */
        $disability = $this->route('disability');
        $id = $disability?->id;

        return [
            'name'   => ['required', 'string', 'max:255', 'unique:disabilities,name' . ($id ? ",{$id}" : '')],
            'status' => ['required', 'in:draft,publish'],
        ];
    }
}
