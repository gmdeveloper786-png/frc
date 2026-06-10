<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\SecureFileStorageService;
use App\Services\StorageAccessService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Authenticated file delivery for uploads (private + legacy public disk).
 * Replaces wide-open public /storage access — required on shared hosts without symlinks.
 */
class PublicStorageController extends Controller
{
    public function __construct(
        private readonly StorageAccessService $access,
        private readonly SecureFileStorageService $files,
    ) {}

    public function show(Request $request, string $path): BinaryFileResponse
    {
        $user = $request->user();
        abort_unless($user !== null, 403);

        $path = str_replace('\\', '/', $path);
        $path = ltrim($path, '/');

        if ($path === '' || str_contains($path, '..')) {
            abort(404);
        }

        if (! $this->access->canAccess($user, $path)) {
            abort(404);
        }

        $absolute = $this->files->absolutePath($path);
        if ($absolute === null || ! is_file($absolute)) {
            abort(404);
        }

        $realRoot = realpath(storage_path('app'));
        $realFile = realpath($absolute);

        if ($realRoot === false || $realFile === false || ! str_starts_with($realFile, $realRoot)) {
            abort(404);
        }

        return response()->file($realFile, [
            'X-Content-Type-Options' => 'nosniff',
            'Content-Disposition'    => 'inline',
        ]);
    }
}
