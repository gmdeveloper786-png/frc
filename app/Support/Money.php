<?php

namespace App\Support;

final class Money
{
    public static function format(float|string|null $value): string
    {
        return number_format((float) $value, 2, '.', '');
    }

    public static function round(float|string|null $value): float
    {
        return round((float) $value, 2);
    }

    public static function add(float|string $a, float|string $b): float
    {
        return (float) bcadd(self::format($a), self::format($b), 2);
    }

    public static function sub(float|string $a, float|string $b): float
    {
        $result = bcsub(self::format($a), self::format($b), 2);

        return (float) (bccomp($result, '0', 2) < 0 ? '0.00' : $result);
    }

    public static function percentOf(float|string $amount, float|string $percent): float
    {
        $ratio = bcdiv(self::format($percent), '100', 6);

        return (float) bcmul(self::format($amount), $ratio, 2);
    }
}
