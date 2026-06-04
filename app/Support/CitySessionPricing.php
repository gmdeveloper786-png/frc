<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Branch;
use App\Services\SettingService;

final class CitySessionPricing
{
    public function __construct(
        private readonly SettingService $settings,
    ) {}

    /**
     * @return array<string, float> City name => PKR per session
     */
    public function all(): array
    {
        $raw = $this->settings->get(SettingKeys::CITY_SESSION_PRICES, '{}');
        if (! is_string($raw) || trim($raw) === '') {
            return self::defaultPrices();
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            return self::defaultPrices();
        }

        $out = [];
        foreach ($decoded as $city => $price) {
            $city = trim((string) $city);
            if ($city === '') {
                continue;
            }
            $out[$city] = max(0, (float) $price);
        }

        return $out !== [] ? $out : self::defaultPrices();
    }

    public function priceForCity(?string $city): ?float
    {
        $city = trim((string) $city);
        if ($city === '') {
            return null;
        }

        foreach ($this->all() as $name => $price) {
            if (strcasecmp($name, $city) === 0) {
                return $price;
            }
        }

        return null;
    }

    public function priceForBranchId(?int $branchId): ?float
    {
        if (! $branchId) {
            return null;
        }

        $city = Branch::query()->whereKey($branchId)->value('city');

        return $this->priceForCity($city !== null ? (string) $city : null);
    }

    /**
     * @return array<int, string> branch_id => city
     */
    public function branchCityMap(iterable $branches): array
    {
        $map = [];
        foreach ($branches as $branch) {
            if ($branch->city) {
                $map[(int) $branch->id] = (string) $branch->city;
            }
        }

        return $map;
    }

    /**
     * @param  array<string, mixed>  $rows
     */
    public static function encodeFromForm(array $rows): string
    {
        $out = [];
        foreach ($rows as $city => $price) {
            $city = trim((string) $city);
            if ($city === '') {
                continue;
            }
            $out[$city] = max(0, (float) $price);
        }

        return json_encode($out, JSON_THROW_ON_ERROR);
    }

    /** @return array<string, float> */
    public static function defaultPrices(): array
    {
        return [
            'Karachi'    => 1000,
            'Faisalabad' => 1500,
        ];
    }
}
