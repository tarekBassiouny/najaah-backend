<?php

declare(strict_types=1);

namespace App\Services\Storage\Contracts;

use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\StreamedResponse;

interface StorageServiceInterface
{
    public function upload(string $path, UploadedFile $file): string;

    public function temporaryUrl(string $path, int $expiresInSeconds): string;

    public function url(string $path): string;

    public function exists(string $path): bool;

    public function download(string $path, string $filename): StreamedResponse;
}
