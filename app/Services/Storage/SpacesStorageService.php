<?php

declare(strict_types=1);

namespace App\Services\Storage;

use App\Services\Storage\Contracts\StorageServiceInterface;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SpacesStorageService implements StorageServiceInterface
{
    public function __construct(private readonly Filesystem $disk) {}

    public function upload(string $path, UploadedFile $file): string
    {
        $path = ltrim($path, '/');
        $directory = trim((string) pathinfo($path, PATHINFO_DIRNAME), '/');
        $filename = (string) pathinfo($path, PATHINFO_BASENAME);

        $stored = $this->disk->putFileAs($directory, $file, $filename, ['visibility' => 'private']);
        if ($stored === false) {
            throw new RuntimeException('Failed to store file.');
        }

        return (string) $stored;
    }

    public function temporaryUrl(string $path, int $expiresInSeconds): string
    {
        $expiresAt = now()->addSeconds($expiresInSeconds);

        try {
            return $this->disk->temporaryUrl($path, $expiresAt);
        } catch (\Throwable $throwable) {
            $url = $this->disk->url($path);

            return $url.(str_contains($url, '?') ? '&' : '?').'expires='.$expiresAt->timestamp;
        }
    }

    public function exists(string $path): bool
    {
        return $this->disk->exists($path);
    }

    public function download(string $path, string $filename): StreamedResponse
    {
        return $this->disk->download($path, $filename);
    }
}
