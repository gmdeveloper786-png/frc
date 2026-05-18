<?php

namespace App\Services;

use App\Support\Money;

class FeeCalculationService
{
    public function __construct(private readonly SettingService $settings) {}
    /**
     * Calculate enrollment totals from raw input.
     *
     * @return array{subtotal, discount_amount, final_total, remaining_amount}
     */
    public function calculate(
        float $pricePerSession,
        int $totalSessions,
        float $discountPercentage
    ): array {
        $subtotal       = Money::round($pricePerSession * $totalSessions);
        $discountAmount = Money::percentOf($subtotal, $discountPercentage);
        $finalTotal     = Money::sub($subtotal, $discountAmount);

        return [
            'subtotal'           => $subtotal,
            'discount_amount'    => $discountAmount,
            'final_total'        => $finalTotal,
            'remaining_amount'   => $finalTotal,
        ];
    }

    /**
     * Calculate total sessions from repeat schedule.
     * Base sessions come from schedule rows. If repeat_weekly, multiply by duration.
     */
    public function calculateTotalSessions(
        int $baseScheduleCount,
        bool $repeatWeekly,
        ?int $durationValue,
        ?string $durationUnit
    ): int {
        if (! $repeatWeekly || ! $durationValue || ! $durationUnit) {
            return $baseScheduleCount;
        }

        $weeks = match ($durationUnit) {
            'weekly'  => $durationValue,
            'monthly' => $durationValue * 4,
            'yearly'  => $durationValue * 52,
            default   => $durationValue,
        };

        return $baseScheduleCount * $weeks;
    }

    public function isHighDiscount(float $discountPercentage): bool
    {
        return $this->settings->isHighDiscount($discountPercentage);
    }
}
