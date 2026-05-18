<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateStaffUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! $this->user()?->isSuperAdmin()) {
            return false;
        }

        $staff = $this->route('user');
        if (! $staff instanceof User) {
            return false;
        }

        return in_array($staff->role?->name, [Role::ADMIN, Role::FINANCE], true);
    }

    public function rules(): array
    {
        /** @var User $staff */
        $staff = $this->route('user');

        return [
            'full_name'        => ['required', 'string', 'max:255'],
            'father_name'      => ['nullable', 'string', 'max:255'],
            'email'            => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($staff->id),
            ],
            'password'         => ['nullable', 'confirmed', Password::min(8)],
            'phone_number'     => ['nullable', 'string', 'max:30'],
            'whatsapp_number'  => ['nullable', 'string', 'max:30'],
            'gender'           => ['nullable', 'in:male,female,other'],
            'date_of_birth'    => ['nullable', 'date'],
            'address'          => ['nullable', 'string', 'max:2000'],
            'status'           => ['required', 'in:active,inactive'],
            'role'             => ['required', 'in:admin,finance'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('password') === '' || $this->input('password') === null) {
            $this->request->remove('password');
            $this->request->remove('password_confirmation');
        }

        $this->merge([
            'email' => is_string($this->input('email')) ? strtolower(trim($this->input('email'))) : $this->input('email'),
        ]);
    }

    public function validated($key = null, $default = null): array
    {
        $data = parent::validated($key, $default);
        unset($data['role_id']);

        return $data;
    }
}
