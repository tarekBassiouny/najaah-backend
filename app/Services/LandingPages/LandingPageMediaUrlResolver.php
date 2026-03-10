<?php

declare(strict_types=1);

namespace App\Services\LandingPages;

use Illuminate\Support\Facades\Storage;

class LandingPageMediaUrlResolver
{
    public function normalizeForStorage(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! $this->isAbsoluteUrl($value)) {
            return ltrim($value, '/');
        }

        $parsedInput = parse_url($value);
        $path = isset($parsedInput['path']) ? ltrim($parsedInput['path'], '/') : '';

        if ($path === '') {
            return $value;
        }

        if ($this->isLandingMediaPath($path)) {
            return $path;
        }

        $disk = (string) config('filesystems.landing_page_media_disk', 'spaces');
        $diskBaseUrl = (string) Storage::disk($disk)->url('/');
        $parsedBase = parse_url($diskBaseUrl);

        $inputHost = $parsedInput['host'] ?? null;
        $baseHost = $parsedBase['host'] ?? null;

        if ($inputHost === null || $baseHost === null || strcasecmp($inputHost, $baseHost) !== 0) {
            return $value;
        }

        $basePath = isset($parsedBase['path']) ? trim($parsedBase['path'], '/') : '';
        if ($basePath === '') {
            return $path;
        }

        if ($path === $basePath) {
            return '';
        }

        if (str_starts_with($path, $basePath.'/')) {
            return substr($path, strlen($basePath) + 1);
        }

        return $value;
    }

    public function resolve(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        if ($this->isAbsoluteUrl($path)) {
            return $path;
        }

        $disk = (string) config('filesystems.landing_page_media_disk', 'spaces');
        $visibility = (string) config('filesystems.disks.'.$disk.'.visibility', 'private');

        if ($visibility === 'public') {
            return Storage::disk($disk)->url($path);
        }

        $ttl = (int) config('filesystems.signed_url_ttl', 900);
        if ($ttl <= 0) {
            $ttl = 900;
        }

        try {
            return Storage::disk($disk)->temporaryUrl($path, now()->addSeconds($ttl));
        } catch (\Throwable) {
            return Storage::disk($disk)->url($path);
        }
    }

    private function isAbsoluteUrl(string $path): bool
    {
        return str_starts_with($path, 'http://') || str_starts_with($path, 'https://');
    }

    private function isLandingMediaPath(string $path): bool
    {
        return str_starts_with($path, 'centers/') && str_contains($path, '/landing-page/');
    }
}
