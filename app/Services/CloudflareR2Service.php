<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CloudflareR2Service
{
    private const SECTION_FOLDERS = [
        'product' => 'products',
        'testimonial' => 'testimonials',
        'category' => 'categories',
        'brand' => 'brands',
        'general' => 'general',
    ];

    public function uploadImage(UploadedFile $file, string $directory = 'products'): array
    {
        $extension = $file->getClientOriginalExtension() ?: 'jpg';
        $fileName = Str::uuid().'.'.$extension;
        $path = trim($directory, '/').'/'.$fileName;

        Storage::disk('r2')->put($path, file_get_contents($file->getRealPath()), 'public');

        return [
            'path' => $path,
            'url' => $this->url($path),
            'name' => $fileName,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ];
    }

    public function directoryFromSection(string $section): string
    {
        return self::SECTION_FOLDERS[$section] ?? self::SECTION_FOLDERS['general'];
    }

    public function url(?string $path): ?string
    {
        if (! is_string($path) || trim($path) === '') {
            return $path;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $path = ltrim($path, '/');
        $configuredUrl = rtrim((string) config('filesystems.disks.r2.url'), '/');

        if ($configuredUrl !== '') {
            return $configuredUrl.'/'.$path;
        }

        $endpoint = rtrim((string) config('filesystems.disks.r2.endpoint'), '/');
        $bucket = (string) config('filesystems.disks.r2.bucket');

        return $endpoint.'/'.$bucket.'/'.$path;
    }
}
