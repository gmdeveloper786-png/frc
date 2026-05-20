<?php

use App\Support\Money;

if (! function_exists('frc_money')) {
    function frc_money(float|string|null $amount): string
    {
        return Money::display($amount);
    }
}

if (! function_exists('frc_pkr')) {
    function frc_pkr(float|string|null $amount): string
    {
        return 'PKR ' . frc_money($amount);
    }
}

if (! function_exists('frc_percent')) {
    function frc_percent(float|string|null $value): string
    {
        return Money::percentDisplay($value);
    }
}

if (! function_exists('frc_money_input')) {
    function frc_money_input(float|string|null $value): string
    {
        return Money::inputValue($value);
    }
}

if (! function_exists('frc_datetime')) {
    /** Display date/time in app timezone with 12-hour clock and AM/PM. */
    function frc_datetime(\DateTimeInterface|string|null $value = null): string
    {
        $tz = config('app.timezone');
        $at = $value === null
            ? now($tz)
            : ($value instanceof \DateTimeInterface
                ? \Illuminate\Support\Carbon::instance($value)->timezone($tz)
                : \Illuminate\Support\Carbon::parse($value, $tz));

        return $at->format('d M Y h:i A');
    }
}
