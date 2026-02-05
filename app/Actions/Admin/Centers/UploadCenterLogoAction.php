<?php

declare(strict_types=1);

namespace App\Actions\Admin\Centers;

use App\Jobs\ProcessCenterLogoJob;
use App\Models\Center;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use App\Services\Storage\Contracts\StorageServiceInterface;
use App\Services\Storage\StoragePathResolver;
use App\Support\AuditActions;
use Illuminate\Http\UploadedFile;

class UploadCenterLogoAction
{
    public function __construct(
        private readonly StorageServiceInterface $storageService,
        private readonly StoragePathResolver $pathResolver,
        private readonly AuditLogService $auditLogService
    ) {}

    public function execute(Center $center, UploadedFile $logo, ?User $actor = null): Center
    {
        $path = $this->pathResolver->centerLogo($center->id, $logo->hashName());
        $storedPath = $this->storageService->upload($path, $logo);

        $center->logo_url = $storedPath;
        $center->save();
        $this->auditLogService->log($actor, $center, AuditActions::CENTER_LOGO_UPDATED, [
            'logo_url' => $storedPath,
        ]);

        ProcessCenterLogoJob::dispatch($center->id, (string) $center->logo_url);

        return $center->fresh(['setting']) ?? $center;
    }
}
