<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Serves files from storage/app/public without requiring php artisan storage:link (symlink).
 * Required on shared hosts where symlink() is disabled.
 */
class PublicStorageController extends Controller
{
    public function show(Request $request, string $path): BinaryFileResponse
    {
        $path = str_replace('\\', '/', $path);
        $path = ltrim($path, '/');

        if ($path === '' || str_contains($path, '..')) {
            abort(404);
        }

        $root = storage_path('app/public');
        $fullPath = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);

        if (! File::isFile($fullPath)) {
            abort(404);
        }

        $realRoot = realpath($root);
        $realFile = realpath($fullPath);

        if ($realRoot === false || $realFile === false || ! str_starts_with($realFile, $realRoot)) {
            abort(404);
        }

        return response()->file($realFile);
    }
}
