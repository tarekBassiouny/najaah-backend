<?php

declare(strict_types=1);

namespace App\Actions\Admin\Centers;

use App\Models\Center;
use App\Services\Storage\Contracts\StorageServiceInterface;
use App\Services\Storage\StoragePathResolver;
use Illuminate\Http\UploadedFile;

class UploadCenterLogoAction
{
    public function __construct(
        private readonly StorageServiceInterface $storageService,
        private readonly StoragePathResolver $pathResolver
    ) {}

    public function execute(Center $center, UploadedFile $logo): Center
    {
        $path = $this->pathResolver->centerLogo($center->id, $logo->hashName());
        $storedPath = $this->storageService->upload($path, $logo);

        $center->logo_url = $storedPath;
        $center->save();

        return $center->fresh(['setting']) ?? $center;
    }
}
