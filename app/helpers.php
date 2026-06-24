<?php

use App\Support\Money;
use App\Support\SearchPattern;

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

if (! function_exists('frc_storage_url')) {
    /**
     * Public URL for files on the "public" disk (storage/app/public).
     * Served by PublicStorageController when symlink is unavailable on the server.
     */
    function frc_storage_url(?string $relativePath): ?string
    {
        if ($relativePath === null || trim($relativePath) === '') {
            return null;
        }

        $path = ltrim(str_replace('\\', '/', $relativePath), '/');

        return url('/storage/' . $path);
    }
}

if (! function_exists('frc_storage_extension')) {
    function frc_storage_extension(?string $relativePath): string
    {
        if ($relativePath === null || trim($relativePath) === '') {
            return '';
        }

        $path = ltrim(str_replace('\\', '/', $relativePath), '/');

        return strtolower(pathinfo($path, PATHINFO_EXTENSION));
    }
}

if (! function_exists('frc_storage_icon')) {
    function frc_storage_icon(?string $relativePath): string
    {
        return match (frc_storage_extension($relativePath)) {
            'pdf'                       => 'fa-file-pdf',
            'jpg', 'jpeg', 'png', 'webp' => 'fa-file-image',
            default                     => 'fa-file-lines',
        };
    }
}

if (! function_exists('frc_storage_label')) {
    function frc_storage_label(?string $relativePath, ?int $number = null): string
    {
        $base = match (frc_storage_extension($relativePath)) {
            'pdf'                       => 'PDF document',
            'jpg', 'jpeg', 'png', 'webp' => 'Image document',
            default                     => 'Document',
        };

        return $number !== null ? $base . ' ' . $number : $base;
    }
}

if (! function_exists('frc_storage_meta')) {
    function frc_storage_meta(?string $relativePath): string
    {
        $ext = frc_storage_extension($relativePath);

        return $ext !== '' ? strtoupper($ext) . ' file' : 'Uploaded file';
    }
}

if (! function_exists('frc_like_pattern')) {
    /** Safe partial-match pattern for SQL LIKE (wildcards escaped). */
    function frc_like_pattern(string $term): string
    {
        return SearchPattern::contains($term);
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
