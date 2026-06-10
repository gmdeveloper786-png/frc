<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Security response headers
    |--------------------------------------------------------------------------
    |
    | Applied on every HTTP response via SecurityHeaders middleware.
    | Disable locally with SECURITY_HEADERS=false if a proxy already sets them.
    |
    */

    'headers_enabled' => env('SECURITY_HEADERS', true),

    'csp_enabled' => env('SECURITY_CSP', env('APP_ENV') === 'production'),

    /** When true, CSP violations are reported but not blocked (useful when tuning policy). */
    'csp_report_only' => env('SECURITY_CSP_REPORT_ONLY', false),

    'frame_options' => env('SECURITY_FRAME_OPTIONS', 'SAMEORIGIN'),

    'referrer_policy' => env('SECURITY_REFERRER_POLICY', 'strict-origin-when-cross-origin'),

    /**
     * HSTS is sent only on HTTPS responses. max-age in seconds (1 year default).
     * includeSubDomains / preload can be enabled once HTTPS is stable on all hosts.
     */
    'hsts' => [
        'enabled'      => env('SECURITY_HSTS', env('APP_ENV') === 'production'),
        'max_age'      => (int) env('SECURITY_HSTS_MAX_AGE', 31_536_000),
        'include_subdomains' => env('SECURITY_HSTS_SUBDOMAINS', false),
        'preload'      => env('SECURITY_HSTS_PRELOAD', false),
    ],

    /**
     * Extra script/style/font hosts used by Blade layouts (keep in sync with layouts/*.blade.php).
     */
    'csp_cdn_hosts' => [
        'https://cdnjs.cloudflare.com',
        'https://cdn.jsdelivr.net',
    ],

];
