<?php

declare(strict_types=1);

namespace App\Services\Videos;

use App\Enums\MediaSourceType;
use App\Enums\VideoLifecycleStatus;
use App\Enums\VideoUploadStatus;
use App\Models\Center;
use App\Models\User;
use App\Models\Video;
use App\Services\Audit\AuditLogService;
use App\Services\Centers\CenterScopeService;
use App\Services\Videos\Contracts\VideoServiceInterface;
use App\Support\AuditActions;
use App\Support\Guards\RejectNonScalarInput;

class VideoService implements VideoServiceInterface
{
    public function __construct(
        private readonly CenterScopeService $centerScopeService,
        private readonly AuditLogService $auditLogService
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Center $center, User $admin, array $data): Video
    {
        if (! $this->centerScopeService->isSystemSuperAdmin($admin)) {
            $this->centerScopeService->assertAdminCenterId($admin, $center->id);
        }

        RejectNonScalarInput::validate($data, ['title', 'description']);

        $payload = $data;
        if (array_key_exists('title', $payload)) {
            $payload['title_translations'] = $payload['title'];
            unset($payload['title']);
        }

        if (array_key_exists('description', $payload)) {
            $payload['description_translations'] = $payload['description'];
            unset($payload['description']);
        }

        $sourceType = $this->resolveSourceType($payload);

        $payload['center_id'] = $center->id;
        $payload['created_by'] = $admin->id;
        $payload['source_type'] = $sourceType;

        if ($sourceType === MediaSourceType::Url) {
            $payload['source_provider'] = $this->resolveUrlProvider($payload);
            $payload['encoding_status'] = VideoUploadStatus::Ready;
            $payload['lifecycle_status'] = VideoLifecycleStatus::Ready;
            $payload['upload_session_id'] = null;
            $payload['source_id'] = null;
        } else {
            $payload['source_provider'] = $payload['source_provider'] ?? 'bunny';
            $payload['encoding_status'] = VideoUploadStatus::Pending;
            $payload['lifecycle_status'] = VideoLifecycleStatus::Pending;
            $payload['source_url'] = null;
        }

        $video = Video::create($payload);

        $this->auditLogService->log($admin, $video, AuditActions::VIDEO_CREATED, [
            'center_id' => $center->id,
        ]);

        return $video;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Video $video, User $admin, array $data): Video
    {
        if (! $this->centerScopeService->isSystemSuperAdmin($admin)) {
            $this->centerScopeService->assertAdminCenterId($admin, $video->center_id);
        }

        RejectNonScalarInput::validate($data, ['title', 'description']);
        $payload = $data;
        if (array_key_exists('title', $payload)) {
            $payload['title_translations'] = $payload['title'];
            unset($payload['title']);
        }

        if (array_key_exists('description', $payload)) {
            $payload['description_translations'] = $payload['description'];
            unset($payload['description']);
        }

        $video->update($payload);

        $this->auditLogService->log($admin, $video, AuditActions::VIDEO_UPDATED, [
            'updated_fields' => array_keys($payload),
        ]);

        return $video->fresh(['uploadSession', 'creator']) ?? $video;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function resolveSourceType(array $payload): MediaSourceType
    {
        $input = $payload['source_type'] ?? null;

        if ($input instanceof MediaSourceType) {
            return $input;
        }

        if (is_string($input)) {
            return strtolower($input) === 'url'
                ? MediaSourceType::Url
                : MediaSourceType::Upload;
        }

        return isset($payload['source_url']) && is_string($payload['source_url']) && $payload['source_url'] !== ''
            ? MediaSourceType::Url
            : MediaSourceType::Upload;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function resolveUrlProvider(array $payload): string
    {
        if (isset($payload['source_provider']) && is_string($payload['source_provider']) && trim($payload['source_provider']) !== '') {
            return strtolower(trim($payload['source_provider']));
        }

        $sourceUrl = isset($payload['source_url']) && is_string($payload['source_url'])
            ? strtolower($payload['source_url'])
            : '';

        if ($sourceUrl === '') {
            return 'custom';
        }

        return match (true) {
            str_contains($sourceUrl, 'youtube.com'), str_contains($sourceUrl, 'youtu.be') => 'youtube',
            str_contains($sourceUrl, 'vimeo.com') => 'vimeo',
            str_contains($sourceUrl, 'zoom.us') => 'zoom',
            default => 'custom',
        };
    }

    public function delete(Video $video, User $admin): void
    {
        if (! $this->centerScopeService->isSystemSuperAdmin($admin)) {
            $this->centerScopeService->assertAdminCenterId($admin, $video->center_id);
        }

        $video->delete();

        $this->auditLogService->log($admin, $video, AuditActions::VIDEO_DELETED);
    }
}
