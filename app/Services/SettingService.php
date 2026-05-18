<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Setting;
use App\Support\SettingKeys;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class SettingService
{
    private const CACHE_KEY = 'frc.app.settings';

    /** @var array<string, string>|null */
    private ?array $loaded = null;

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->all()[$key] ?? null;

        return $value !== null && $value !== '' ? $value : $default;
    }

    /** @return array<string, string> */
    public function all(): array
    {
        if ($this->loaded !== null) {
            return $this->loaded;
        }

        $this->loaded = Cache::remember(self::CACHE_KEY, 3600, function (): array {
            $stored = Setting::query()->pluck('value', 'key')->all();
            $merged = [];

            foreach (SettingKeys::defaults() as $key => $meta) {
                $merged[$key] = (string) ($stored[$key] ?? $meta['value']);
            }

            return $merged;
        });

        return $this->loaded;
    }

    public function highDiscountThreshold(): float
    {
        return (float) $this->get(SettingKeys::HIGH_DISCOUNT_THRESHOLD, 50);
    }

    public function isHighDiscount(float $discountPercentage): bool
    {
        return $discountPercentage > $this->highDiscountThreshold();
    }

    public function childRegistrationEnabled(): bool
    {
        return filter_var($this->get(SettingKeys::CHILD_REGISTRATION_ENABLED, '1'), FILTER_VALIDATE_BOOLEAN);
    }

    /** @return array<string, mixed> */
    public function forViews(): array
    {
        return [
            'organisation_name'       => (string) $this->get(SettingKeys::ORGANISATION_NAME, 'Faizan Rehabilitation Centre'),
            'organisation_short_name'   => (string) $this->get(SettingKeys::ORGANISATION_SHORT_NAME, 'Faizan Rehab'),
            'organisation_tagline'      => (string) $this->get(SettingKeys::ORGANISATION_TAGLINE, 'Management System'),
            'receipt_logo_text'         => (string) $this->get(SettingKeys::RECEIPT_LOGO_TEXT, 'FRC'),
            'contact_phone'             => (string) $this->get(SettingKeys::CONTACT_PHONE, ''),
            'contact_email'             => (string) $this->get(SettingKeys::CONTACT_EMAIL, ''),
            'contact_address'           => (string) $this->get(SettingKeys::CONTACT_ADDRESS, ''),
            'high_discount_threshold'   => $this->highDiscountThreshold(),
            'bank_account_title'        => (string) $this->get(SettingKeys::BANK_ACCOUNT_TITLE, ''),
            'bank_account_number'       => (string) $this->get(SettingKeys::BANK_ACCOUNT_NUMBER, ''),
            'bank_name'                 => (string) $this->get(SettingKeys::BANK_NAME, ''),
            'payment_instructions'      => (string) $this->get(SettingKeys::PAYMENT_INSTRUCTIONS, ''),
            'child_registration_enabled'=> $this->childRegistrationEnabled(),
        ];
    }

    /** @return Collection<string, Collection<int, Setting>> */
    public function groupedForForm(): Collection
    {
        $labels = [
            'general'      => 'General & branding',
            'enrollment'   => 'Enrollment rules',
            'payments'     => 'Payments & bank details',
            'registration' => 'Child registration',
        ];

        $settings = Setting::query()->orderBy('key')->get()->keyBy('key');

        return collect(SettingKeys::defaults())
            ->map(function (array $meta, string $key) use ($settings): Setting {
                $row = $settings->get($key);

                return $row ?? new Setting([
                    'key'   => $key,
                    'value' => $meta['value'],
                    'type'  => $meta['type'],
                    'group' => $meta['group'],
                ]);
            })
            ->groupBy('group')
            ->map(fn (Collection $items, string $group) => $items->values())
            ->sortKeys()
            ->mapWithKeys(fn (Collection $items, string $group) => [
                $labels[$group] ?? ucfirst($group) => $items,
            ]);
    }

    /** @param  array<string, mixed>  $data */
    public function sync(array $data, int $updatedBy): void
    {
        $allowed = array_keys(SettingKeys::defaults());

        foreach ($allowed as $key) {
            if (! array_key_exists($key, $data)) {
                if (SettingKeys::defaults()[$key]['type'] === 'boolean') {
                    Setting::updateOrCreate(
                        ['key' => $key],
                        ['value' => '0', 'type' => 'boolean', 'group' => SettingKeys::defaults()[$key]['group']]
                    );
                }

                continue;
            }

            $meta  = SettingKeys::defaults()[$key];
            $value = $data[$key];

            if ($meta['type'] === 'boolean') {
                $value = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
            } else {
                $value = is_scalar($value) ? trim((string) $value) : '';
            }

            Setting::updateOrCreate(
                ['key' => $key],
                [
                    'value' => $value,
                    'type'  => $meta['type'],
                    'group' => $meta['group'],
                ]
            );
        }

        $this->flushCache();
    }

    public function ensureDefaults(): void
    {
        foreach (SettingKeys::defaults() as $key => $meta) {
            Setting::updateOrCreate(
                ['key' => $key],
                [
                    'value' => $meta['value'],
                    'type'  => $meta['type'],
                    'group' => $meta['group'],
                ]
            );
        }

        $this->flushCache();
    }

    public function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        $this->loaded = null;
    }

    public function label(string $key): string
    {
        return match ($key) {
            SettingKeys::ORGANISATION_NAME       => 'Organisation name',
            SettingKeys::ORGANISATION_SHORT_NAME => 'Short name (sidebar)',
            SettingKeys::ORGANISATION_TAGLINE    => 'Tagline',
            SettingKeys::RECEIPT_LOGO_TEXT       => 'Receipt logo text',
            SettingKeys::CONTACT_PHONE           => 'Contact phone',
            SettingKeys::CONTACT_EMAIL           => 'Contact email',
            SettingKeys::CONTACT_ADDRESS         => 'Contact address',
            SettingKeys::HIGH_DISCOUNT_THRESHOLD => 'High discount threshold (%)',
            SettingKeys::BANK_ACCOUNT_TITLE      => 'Bank account title',
            SettingKeys::BANK_ACCOUNT_NUMBER     => 'Bank account number',
            SettingKeys::BANK_NAME               => 'Bank name',
            SettingKeys::PAYMENT_INSTRUCTIONS    => 'Payment instructions (child upload slip)',
            SettingKeys::CHILD_REGISTRATION_ENABLED => 'Allow new child self-registration',
            SettingKeys::REGISTRATION_SUCCESS_MESSAGE => 'Message after registration',
            default => str_replace('_', ' ', ucfirst($key)),
        };
    }
}
