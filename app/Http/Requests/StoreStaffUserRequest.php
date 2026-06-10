<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreStaffUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isSuperAdmin();
    }

    public function rules(): array
    {
        return [
            'full_name'        => ['required', 'string', 'max:255'],
            'father_name'      => ['nullable', 'string', 'max:255'],
            'email'            => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password'         => ['required', 'confirmed', Password::defaults()],
            'phone_number'     => ['nullable', 'string', 'max:30'],
            'whatsapp_number'  => ['nullable', 'string', 'max:30'],
            'gender'           => ['nullable', 'in:male,female,other'],
            'date_of_birth'    => ['nullable', 'date', 'before:today'],
            'address'          => ['nullable', 'string', 'max:2000'],
            'status'           => ['required', 'in:active,inactive'],
            'role'             => ['required', 'in:admin,finance,approval_discount'],
            'branch_id'        => [
                Rule::requiredIf(fn (): bool => $this->input('role') === Role::ADMIN),
                'nullable',
                'integer',
                'exists:branches,id',
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $merge = [
            'email' => is_string($this->input('email')) ? strtolower(trim($this->input('email'))) : $this->input('email'),
        ];

        if (in_array($this->input('role'), [Role::FINANCE, Role::APPROVAL_DISCOUNT], true)) {
            $merge['branch_id'] = null;
        }

        $this->merge($merge);
    }

    public function validated($key = null, $default = null): array
    {
        $data = parent::validated($key, $default);
        unset($data['role_id']);

        return $data;
    }
}
