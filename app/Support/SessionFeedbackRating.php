<?php

declare(strict_types=1);

namespace App\Support;

final class SessionFeedbackRating
{
    public const MIN = 1;

    public const MAX = 5;

    /** @return array<int, string> */
    public static function options(): array
    {
        return [
            1 => 'No progress',
            2 => 'Minimal progress',
            3 => 'Moderate progress',
            4 => 'Good progress',
            5 => 'Excellent progress',
        ];
    }

    public static function label(int $rating): string
    {
        return self::options()[$rating] ?? (string) $rating;
    }

    public static function isValid(int $rating): bool
    {
        return $rating >= self::MIN && $rating <= self::MAX;
    }

    public static function overallLabel(?float $average): ?string
    {
        if ($average === null) {
            return null;
        }

        $rounded = (int) round($average);
        $rounded = max(self::MIN, min(self::MAX, $rounded));

        return self::label($rounded);
    }

    public static function ratingPercent(int $rating): int
    {
        $rating = max(self::MIN, min(self::MAX, $rating));

        return (int) round((($rating - self::MIN) / (self::MAX - self::MIN)) * 100);
    }

    public static function averagePercent(?float $average): ?int
    {
        if ($average === null) {
            return null;
        }

        $average = max((float) self::MIN, min((float) self::MAX, $average));

        return (int) round((($average - self::MIN) / (self::MAX - self::MIN)) * 100);
    }

    public static function chartColor(int $rating): string
    {
        $rating = max(self::MIN, min(self::MAX, $rating));

        return match ($rating) {
            1       => 'rgba(220, 53, 69, 0.85)',
            2       => 'rgba(245, 158, 11, 0.88)',
            3       => 'rgba(17, 81, 124, 0.75)',
            4       => 'rgba(22, 172, 172, 0.82)',
            default => 'rgba(16, 130, 110, 0.92)',
        };
    }
}
