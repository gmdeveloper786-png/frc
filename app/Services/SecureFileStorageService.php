<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SecureFileStorageService
{
    /** @var list<string> */
    private const BLOCKED_EXTENSIONS = [
        'php',
        'php3',
        'php4',
        'php5',
        'phtml',
        'phar',
        'exe',
        'js',
        'mjs',
        'html',
        'htm',
        'svg',
        'xml',
        'sh',
        'bat',
        'cmd',
        'com',
        'asp',
        'aspx',
        'jsp',
    ];

    /** @var array<string, string> */
    private const MIME_TO_EXTENSION = [
        'application/pdf' => 'pdf',
        'image/jpeg'      => 'jpg',
        'image/png'       => 'png',
        'image/webp'      => 'webp',
    ];

    public function store(UploadedFile $file, string $directory, string $disk = 'private'): string
    {
        $this->guardAgainstDangerousFile($file);

        $directory = trim(str_replace('\\', '/', $directory), '/');
        $extension = $this->resolveSafeExtension($file);
        $filename  = Str::random(40) . '.' . $extension;

        return $file->storeAs($directory, $filename, $disk);
    }

    /**
     * @param  array<int, UploadedFile>  $files
     * @return list<string>
     */
    public function storeMany(array $files, string $directory, string $disk = 'private'): array
    {
        $paths = [];
        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $paths[] = $this->store($file, $directory, $disk);
            }
        }

        return $paths;
    }

    public function delete(?string $path, ?string $disk = null): void
    {
        if ($path === null || trim($path) === '') {
            return;
        }

        $disk ??= $this->diskForPath($path);
        if (Storage::disk($disk)->exists($path)) {
            Storage::disk($disk)->delete($path);
        }
    }

    public function diskForPath(string $path): string
    {
        if (Storage::disk('private')->exists($path)) {
            return 'private';
        }

        return 'public';
    }

    public function absolutePath(string $path): ?string
    {
        $disk = $this->diskForPath($path);

        if (! Storage::disk($disk)->exists($path)) {
            return null;
        }

        return Storage::disk($disk)->path($path);
    }

    private function guardAgainstDangerousFile(UploadedFile $file): void
    {
        $original = strtolower($file->getClientOriginalName());

        foreach (explode('.', $original) as $segment) {
            if (in_array($segment, self::BLOCKED_EXTENSIONS, true)) {
                throw ValidationException::withMessages([
                    $file->getClientOriginalName() => ['This file type is not allowed.'],
                ]);
            }
        }

        $extension = strtolower((string) $file->getClientOriginalExtension());
        if ($extension !== '' && in_array($extension, self::BLOCKED_EXTENSIONS, true)) {
            throw ValidationException::withMessages([
                $file->getClientOriginalName() => ['This file type is not allowed.'],
            ]);
        }
    }

    private function resolveSafeExtension(UploadedFile $file): string
    {
        $mime = (string) $file->getMimeType();

        if (isset(self::MIME_TO_EXTENSION[$mime])) {
            return self::MIME_TO_EXTENSION[$mime];
        }

        throw ValidationException::withMessages([
            $file->getClientOriginalName() => ['The uploaded file type is not allowed.'],
        ]);
    }
}
