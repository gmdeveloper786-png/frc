<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Support\SettingKeys;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return (bool) ($user?->isSuperAdmin() || $user?->hasPermission('manage_settings'));
    }

    public function rules(): array
    {
        return [
            'organisation_name'        => ['required', 'string', 'max:255'],
            'organisation_short_name'  => ['required', 'string', 'max:80'],
            'organisation_tagline'     => ['nullable', 'string', 'max:120'],
            'receipt_logo_text'        => ['required', 'string', 'max:20'],
            'contact_phone'            => ['nullable', 'string', 'max:30'],
            'contact_email'            => ['nullable', 'email', 'max:255'],
            'contact_address'          => ['nullable', 'string', 'max:2000'],
            'high_discount_threshold'  => ['required', 'numeric', 'min:0', 'max:100'],
            'bank_account_title'       => ['nullable', 'string', 'max:255'],
            'bank_account_number'      => ['nullable', 'string', 'max:80'],
            'bank_name'                => ['nullable', 'string', 'max:255'],
            'payment_instructions'     => ['nullable', 'string', 'max:5000'],
            'child_registration_enabled' => ['nullable', 'boolean'],
            'registration_success_message' => ['nullable', 'string', 'max:1000'],
            'city_session_prices'          => ['nullable', 'array'],
            'city_session_prices.*'        => ['nullable', 'numeric', 'min:0'],
        ];
    }

    /** @return array<string, mixed> */
    public function settingsPayload(): array
    {
        $data = $this->validated();
        $data[SettingKeys::CHILD_REGISTRATION_ENABLED] = $this->boolean('child_registration_enabled');

        if ($this->has('city_session_prices') && is_array($this->input('city_session_prices'))) {
            $data[SettingKeys::CITY_SESSION_PRICES] = \App\Support\CitySessionPricing::encodeFromForm(
                $this->input('city_session_prices'),
            );
        }

        return $data;
    }
}
