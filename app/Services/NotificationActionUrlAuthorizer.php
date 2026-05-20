<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;

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

        if (str_starts_with($url, '/')) {
            $path = $url;
            $absolute = rtrim((string) config('app.url'), '/') . $path;
        } else {
            $parts = parse_url($url);
            if ($parts === false || empty($parts['scheme']) || empty($parts['host'])) {
                return null;
            }
            $path = (string) (parse_url($url, PHP_URL_PATH) ?? '/');
            $query = parse_url($url, PHP_URL_QUERY);
            if (is_string($query) && $query !== '') {
                $path .= '?' . $query;
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

}
