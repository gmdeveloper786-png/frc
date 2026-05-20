<?php

namespace App\Support;

final class Money
{
    /** UI / receipts: whole PKR amounts with thousands separators (no .00). */
    public static function display(float|string|null $value): string
    {
        return number_format((int) round((float) $value), 0, '.', ',');
    }

    /** HTML number inputs — no decimal suffix. */
    public static function inputValue(float|string|null $value): string
    {
        return (string) (int) round((float) $value);
    }

    /** Discount % shown without trailing zeros when whole. */
    public static function percentDisplay(float|string|null $value): string
    {
        $n = (float) $value;
        if ($n == floor($n)) {
            return (string) (int) $n;
        }

        return rtrim(rtrim(number_format($n, 2, '.', ''), '0'), '.');
    }

    /** CSV / exports: plain integer string. */
    public static function exportAmount(float|string|null $value): string
    {
        return (string) (int) round((float) $value);
    }

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
