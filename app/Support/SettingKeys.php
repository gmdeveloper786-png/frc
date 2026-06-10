<?php

declare(strict_types=1);

namespace App\Support;

final class SettingKeys
{
    public const ORGANISATION_NAME       = 'organisation_name';
    public const ORGANISATION_SHORT_NAME = 'organisation_short_name';
    public const ORGANISATION_TAGLINE    = 'organisation_tagline';
    public const RECEIPT_LOGO_TEXT       = 'receipt_logo_text';
    public const CONTACT_PHONE           = 'contact_phone';
    public const CONTACT_EMAIL           = 'contact_email';
    public const CONTACT_ADDRESS         = 'contact_address';

    public const HIGH_DISCOUNT_THRESHOLD = 'high_discount_threshold';

    /** JSON map: city name => PKR price per session (e.g. {"Karachi":1000,"Faisalabad":1500}). */
    public const CITY_SESSION_PRICES = 'city_session_prices';

    public const BANK_ACCOUNT_TITLE   = 'bank_account_title';
    public const BANK_ACCOUNT_NUMBER  = 'bank_account_number';
    public const BANK_NAME            = 'bank_name';
    public const PAYMENT_INSTRUCTIONS = 'payment_instructions';

    public const CHILD_REGISTRATION_ENABLED = 'child_registration_enabled';
    public const REGISTRATION_SUCCESS_MESSAGE = 'registration_success_message';

    /** @return array<string, array{value: string, type: string, group: string}> */
    public static function defaults(): array
    {
        return [
            self::ORGANISATION_NAME => [
                'value' => 'Faizan Rehabilitation Centre',
                'type'  => 'string',
                'group' => 'general',
            ],
            self::ORGANISATION_SHORT_NAME => [
                'value' => 'Faizan Rehab',
                'type'  => 'string',
                'group' => 'general',
            ],
            self::ORGANISATION_TAGLINE => [
                'value' => 'Management System',
                'type'  => 'string',
                'group' => 'general',
            ],
            self::RECEIPT_LOGO_TEXT => [
                'value' => 'FRC',
                'type'  => 'string',
                'group' => 'general',
            ],
            self::CONTACT_PHONE => [
                'value' => '',
                'type'  => 'string',
                'group' => 'general',
            ],
            self::CONTACT_EMAIL => [
                'value' => '',
                'type'  => 'string',
                'group' => 'general',
            ],
            self::CONTACT_ADDRESS => [
                'value' => '',
                'type'  => 'string',
                'group' => 'general',
            ],
            self::HIGH_DISCOUNT_THRESHOLD => [
                'value' => '50',
                'type'  => 'number',
                'group' => 'enrollment',
            ],
            self::CITY_SESSION_PRICES => [
                'value' => '{"Karachi":1000,"Faisalabad":1000,"Lahore":1000,"Sialkot":1000}',
                'type'  => 'json',
                'group' => 'enrollment',
            ],
            self::BANK_ACCOUNT_TITLE => [
                'value' => '',
                'type'  => 'string',
                'group' => 'payments',
            ],
            self::BANK_ACCOUNT_NUMBER => [
                'value' => '',
                'type'  => 'string',
                'group' => 'payments',
            ],
            self::BANK_NAME => [
                'value' => '',
                'type'  => 'string',
                'group' => 'payments',
            ],
            self::PAYMENT_INSTRUCTIONS => [
                'value' => 'Upload a clear photo or PDF of your bank transfer / mobile wallet receipt after payment.',
                'type'  => 'text',
                'group' => 'payments',
            ],
            self::CHILD_REGISTRATION_ENABLED => [
                'value' => '1',
                'type'  => 'boolean',
                'group' => 'registration',
            ],
            self::REGISTRATION_SUCCESS_MESSAGE => [
                'value' => 'Your registration has been submitted. Please wait for admin approval.',
                'type'  => 'text',
                'group' => 'registration',
            ],
        ];
    }
}
