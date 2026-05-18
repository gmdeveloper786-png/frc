<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;

/**
 * Ensures notification action URLs stay on this app and match the user's role.
 */
final class NotificationActionUrlAuthorizer
{
    public function authorizedUrlFor(User $user, string $url): ?string
    {
        $url = trim($url);
        if ($url === '') {
            return null;
        }

        if (Str::startsWith($url, '/')) {
            $path = $url;
            $absolute = rtrim((string) config('app.url'), '/') . $path;
        } else {
            $parts = parse_url($url);
            if ($parts === false || empty($parts['scheme']) || empty($parts['host'])) {
                return null;
            }
            $appParts = parse_url((string) config('app.url'));
            if ($appParts === false || empty($appParts['host'])) {
                return null;
            }
            if (! $this->hostsAreSameApp((string) $parts['host'], (string) $appParts['host'])) {
                return null;
            }
            $path = $parts['path'] ?? '/';
            if (isset($parts['query'])) {
                $path .= '?' . $parts['query'];
            }
            $absolute = rtrim((string) config('app.url'), '/') . $path;
        }

        $pathOnly = parse_url($absolute, PHP_URL_PATH) ?? '/';

        if (! $this->canAccessPath($user, $pathOnly)) {
            return null;
        }

        return $absolute;
    }

    private function canAccessPath(User $user, string $path): bool
    {
        $path = '/' . ltrim($path, '/');

        $childPrefixes = [
            '/my-assessments',
            '/my-enrollment',
            '/my-payments',
            '/my-schedule',
            '/upload-slip',
            '/my-profile',
            '/child/dashboard',
        ];
        foreach ($childPrefixes as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return $user->isChild();
            }
        }

        if (str_starts_with($path, '/super-admin')) {
            return $user->isSuperAdmin();
        }

        if (str_starts_with($path, '/admin/')) {
            return $user->isAdmin();
        }

        if (str_starts_with($path, '/therapist/')) {
            return $user->isTherapist();
        }

        if (str_starts_with($path, '/finance/')) {
            return $user->isFinance();
        }

        if ($user->isFinance()) {
            return str_starts_with($path, '/finance/');
        }

        if ($user->isChild()) {
            return false;
        }

        return $user->isSuperAdmin() || $user->isAdmin() || $user->isFinance() || $user->isTherapist();
    }

    /**
     * Same hostname, or both are local dev loopback names (localhost vs 127.0.0.1 mismatch).
     */
    private function hostsAreSameApp(string $urlHost, string $appHost): bool
    {
        if (strcasecmp($urlHost, $appHost) === 0) {
            return true;
        }

        return $this->isLoopbackHost($urlHost) && $this->isLoopbackHost($appHost);
    }

    private function isLoopbackHost(string $host): bool
    {
        $h = strtolower(trim($host));

        return $h === 'localhost'
            || $h === '127.0.0.1'
            || $h === '[::1]'
            || $h === '::1';
    }
}
