<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CloudflareR2Service
{
    /**
     * Map dashboard upload section -> folder path in R2.
     */
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

        Storage::disk('r2')->put($path, file_get_contents($file->getRealPath()), [
            'visibility' => 'public',
            'ContentType' => $file->getMimeType(),
        ]);

        return [
            'path' => $path,
            'url' => $this->resolvePublicUrl($path),
        ];
    }

    public function directoryFromSection(string $section): string
    {
        $normalized = Str::lower(trim($section));

        return self::SECTION_FOLDERS[$normalized] ?? self::SECTION_FOLDERS['general'];
    }

    private function resolvePublicUrl(string $path): string
    {
        $configuredUrl = rtrim((string) config('filesystems.disks.r2.url'), '/');

        if ($configuredUrl !== '') {
            return $configuredUrl.'/'.$path;
        }

        $endpoint = rtrim((string) config('filesystems.disks.r2.endpoint'), '/');
        $bucket = (string) config('filesystems.disks.r2.bucket');

        return $endpoint.'/'.$bucket.'/'.$path;
    }
}
