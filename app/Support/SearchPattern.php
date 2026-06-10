<?php

namespace App\Support;

final class SearchPattern
{
    /** Escape SQL LIKE wildcards and wrap for partial match. */
    public static function contains(string $term): string
    {
        $escaped = str_replace(
            ['\\', '%', '_'],
            ['\\\\', '\\%', '\\_'],
            trim($term),
        );

        return '%'.$escaped.'%';
    }
}
