<?php

declare(strict_types=1);

namespace App\Actions\Admin\Videos;

use App\Models\User;
use App\Models\Video;
use App\Services\Audit\AuditLogService;
use App\Services\Storage\Contracts\StorageServiceInterface;
use App\Support\AuditActions;

class ClearVideoThumbnailAction
{
    public function __construct(
        private readonly StorageServiceInterface $storageService,
        private readonly AuditLogService $auditLogService
    ) {}

    public function execute(Video $video, ?User $actor = null): Video
    {
        $previousPath = is_string($video->custom_thumbnail_url) ? $video->custom_thumbnail_url : null;

        $video->custom_thumbnail_url = null;
        $video->save();

        if ($previousPath !== null && $previousPath !== '' && ! $this->isAbsoluteUrl($previousPath)) {
            $this->storageService->delete($previousPath);
        }

        $this->auditLogService->log($actor, $video, AuditActions::VIDEO_THUMBNAIL_CLEARED, []);

        return $video->fresh(['center', 'creator', 'uploadSession']) ?? $video;
    }

    private function isAbsoluteUrl(string $path): bool
    {
        return str_starts_with($path, 'http://') || str_starts_with($path, 'https://');
    }
}
