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
        if (! $admin->hasRole('super_admin')) {
            $this->centerScopeService->assertAdminCenterId($admin, $center->id);
        }

        RejectNonScalarInput::validate($data, ['title', 'description']);

        $payload = $data;
        $payload['title_translations'] = $data['title'] ?? '';
        $payload['description_translations'] = $data['description'] ?? null;
        unset($payload['title'], $payload['description']);

        $payload['center_id'] = $center->id;
        $payload['created_by'] = $admin->id;
        $payload['source_type'] = $payload['source_type'] ?? MediaSourceType::Upload;
        $payload['source_provider'] = $payload['source_provider'] ?? 'bunny';
        $payload['encoding_status'] = VideoUploadStatus::Pending;
        $payload['lifecycle_status'] = VideoLifecycleStatus::Pending;

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
        if (! $admin->hasRole('super_admin')) {
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

    public function delete(Video $video, User $admin): void
    {
        if (! $admin->hasRole('super_admin')) {
            $this->centerScopeService->assertAdminCenterId($admin, $video->center_id);
        }

        $video->delete();

        $this->auditLogService->log($admin, $video, AuditActions::VIDEO_DELETED);
    }
}
