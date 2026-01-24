<?php

declare(strict_types=1);

namespace App\Services\Storage;

use App\Services\Storage\Contracts\StorageServiceInterface;
use Aws\S3\S3Client;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SpacesStorageService implements StorageServiceInterface
{
    private readonly S3Client $s3;

    private readonly string $bucket;

    public function __construct(
        private readonly Filesystem $disk,
        ?S3Client $s3Client = null,
        ?string $bucket = null
    ) {
        $this->bucket = $bucket ?? (string) config('filesystems.disks.spaces.bucket');
        $this->s3 = $s3Client ?? $this->createS3Client();
    }

    private function createS3Client(): S3Client
    {
        return new S3Client([
            'version' => 'latest',
            'region' => (string) config('filesystems.disks.spaces.region', 'us-east-1'),
            'endpoint' => (string) config('filesystems.disks.spaces.endpoint'),
            'credentials' => [
                'key' => (string) config('filesystems.disks.spaces.key'),
                'secret' => (string) config('filesystems.disks.spaces.secret'),
            ],
        ]);
    }

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

    public function temporaryUploadUrl(
        string $path,
        int $expiresInSeconds,
        string $contentType
    ): string {
        $cmd = $this->s3->getCommand('PutObject', [
            'Bucket' => $this->bucket,
            'Key' => ltrim($path, '/'),
            'ContentType' => $contentType,
        ]);

        try {
            $request = $this->s3->createPresignedRequest(
                $cmd,
                '+'.$expiresInSeconds.' seconds'
            );

            return (string) $request->getUri();
        } catch (\Throwable $throwable) {
            throw new RuntimeException(
                'Failed to generate presigned upload URL',
                previous: $throwable
            );
        }
    }

    public function exists(string $path): bool
    {
        return $this->disk->exists($path);
    }

    public function url(string $path): string
    {
        return $this->disk->url($path);
    }

    public function download(string $path, string $filename): StreamedResponse
    {
        return $this->disk->download($path, $filename);
    }
}
