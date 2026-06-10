<?php

namespace App\Support;

final class UploadRules
{
    /** Allowed document / image uploads (PDF + raster images). */
    public static function document(bool $required = true): array
    {
        $rules = [
            'file',
            'mimes:pdf,jpg,jpeg,png,webp',
            'mimetypes:application/pdf,image/jpeg,image/png,image/webp',
            'max:2048',
        ];

        array_unshift($rules, $required ? 'required' : 'nullable');

        return $rules;
    }
}
