<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $nonce = base64_encode(random_bytes(16));
        $request->attributes->set('csp_nonce', $nonce);
        View::share('cspNonce', $nonce);

        /** @var Response $response */
        $response = $next($request);

        if (! config('security.headers_enabled', true)) {
            return $response;
        }

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', (string) config('security.frame_options', 'SAMEORIGIN'));
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', (string) config('security.referrer_policy', 'strict-origin-when-cross-origin'));
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        if ($this->shouldSendHsts($request)) {
            $response->headers->set('Strict-Transport-Security', $this->hstsValue());
        }

        if (config('security.csp_enabled', false)) {
            $header = config('security.csp_report_only', false)
                ? 'Content-Security-Policy-Report-Only'
                : 'Content-Security-Policy';

            $response->headers->set($header, $this->contentSecurityPolicy($nonce));
        }

        return $response;
    }

    private function shouldSendHsts(Request $request): bool
    {
        if (! config('security.hsts.enabled', false)) {
            return false;
        }

        return $request->secure() || config('app.force_https', false);
    }

    private function hstsValue(): string
    {
        $hsts = config('security.hsts', []);
        $value = 'max-age=' . (int) ($hsts['max_age'] ?? 31_536_000);

        if (! empty($hsts['include_subdomains'])) {
            $value .= '; includeSubDomains';
        }

        if (! empty($hsts['preload'])) {
            $value .= '; preload';
        }

        return $value;
    }

    private function contentSecurityPolicy(string $nonce): string
    {
        $cdns = implode(' ', config('security.csp_cdn_hosts', []));

        $directives = [
            "default-src 'self'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'self'",
            "object-src 'none'",
            "script-src 'self' 'nonce-{$nonce}' {$cdns}",
            "style-src 'self' 'unsafe-inline' {$cdns}",
            "font-src 'self' data: {$cdns}",
            "img-src 'self' data: blob:",
            "connect-src 'self'",
        ];

        return implode('; ', $directives);
    }
}
