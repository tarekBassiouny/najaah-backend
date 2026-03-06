<?php

declare(strict_types=1);

namespace App\Actions\Admin\Videos;

use App\Models\User;
use App\Models\Video;
use App\Services\Audit\AuditLogService;
use App\Services\Storage\Contracts\StorageServiceInterface;
use App\Services\Storage\StoragePathResolver;
use App\Support\AuditActions;
use Illuminate\Http\UploadedFile;

class UploadVideoThumbnailAction
{
    public function __construct(
        private readonly StorageServiceInterface $storageService,
        private readonly StoragePathResolver $pathResolver,
        private readonly AuditLogService $auditLogService
    ) {}

    public function execute(Video $video, UploadedFile $thumbnail, ?User $actor = null): Video
    {
        $previousPath = is_string($video->custom_thumbnail_url) ? $video->custom_thumbnail_url : null;
        $path = $this->pathResolver->videoThumbnail(
            (int) $video->center_id,
            (int) $video->id,
            $thumbnail->hashName()
        );
        $storedPath = $this->storageService->upload($path, $thumbnail);

        $video->custom_thumbnail_url = $storedPath;
        $video->save();

        if ($previousPath !== null && $previousPath !== '' && $previousPath !== $storedPath && ! $this->isAbsoluteUrl($previousPath)) {
            $this->storageService->delete($previousPath);
        }

        $this->auditLogService->log($actor, $video, AuditActions::VIDEO_THUMBNAIL_UPDATED, [
            'custom_thumbnail_url' => $storedPath,
        ]);

        return $video->fresh(['center', 'creator', 'uploadSession']) ?? $video;
    }

    private function isAbsoluteUrl(string $path): bool
    {
        return str_starts_with($path, 'http://') || str_starts_with($path, 'https://');
    }
}
