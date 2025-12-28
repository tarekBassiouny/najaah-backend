<?php

declare(strict_types=1);

namespace App\Services\Branding;

use App\Services\Storage\Contracts\StorageServiceInterface;
use Illuminate\Support\Facades\Log;

class CenterLogoUrlResolver
{
    public function __construct(private readonly StorageServiceInterface $storageService) {}

    public function resolve(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        if ($this->isAbsoluteUrl($path)) {
            return $path;
        }

        if (! $this->storageService->exists($path)) {
            Log::warning('Center logo path missing on storage.', ['path' => $path]);

            return null;
        }

        $visibility = (string) config(
            'filesystems.disks.'.config('filesystems.default', 'local').'.visibility',
            'private'
        );

        if ($visibility === 'public') {
            return $this->storageService->url($path);
        }

        $ttl = (int) config('filesystems.signed_url_ttl', 900);
        if ($ttl <= 0) {
            $ttl = 900;
        }

        return $this->storageService->temporaryUrl($path, $ttl);
    }

    private function isAbsoluteUrl(string $path): bool
    {
        return str_starts_with($path, 'http://') || str_starts_with($path, 'https://');
    }
}
