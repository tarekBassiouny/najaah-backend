<?php

declare(strict_types=1);

namespace App\Services\Videos;

use App\Models\Center;
use App\Models\User;
use App\Models\Video;
use App\Services\Centers\CenterScopeService;
use App\Support\Guards\RejectNonScalarInput;

class VideoService
{
    public function __construct(private readonly CenterScopeService $centerScopeService) {}

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
        $payload['source_type'] = $payload['source_type'] ?? 1;
        $payload['source_provider'] = $payload['source_provider'] ?? 'bunny';
        $payload['encoding_status'] = 0;
        $payload['lifecycle_status'] = 0;

        return Video::create($payload);
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

        return $video->fresh(['uploadSession', 'creator']) ?? $video;
    }

    public function delete(Video $video, User $admin): void
    {
        if (! $admin->hasRole('super_admin')) {
            $this->centerScopeService->assertAdminCenterId($admin, $video->center_id);
        }

        $video->delete();
    }
}
