<?php

declare(strict_types=1);

namespace App\Services\Videos;

use App\Enums\VideoUploadStatus;
use App\Filters\Admin\VideoUploadSessionFilters;
use App\Models\Center;
use App\Models\User;
use App\Models\VideoUploadSession;
use App\Services\Centers\CenterScopeService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class VideoUploadSessionQueryService
{
    public function __construct(private readonly CenterScopeService $centerScopeService) {}

    /**
     * @return LengthAwarePaginator<VideoUploadSession>
     */
    public function paginate(User $admin, VideoUploadSessionFilters $filters): LengthAwarePaginator
    {
        $query = VideoUploadSession::query()
            ->with(['videos'])
            ->orderByDesc('created_at');

        $query = $this->applyScope($query, $admin);
        $query = $this->applyFilters($query, $admin, $filters);

        return $query->paginate(
            $filters->perPage,
            ['*'],
            'page',
            $filters->page
        );
    }

    /**
     * @return LengthAwarePaginator<VideoUploadSession>
     */
    public function paginateForCenter(User $admin, Center $center, VideoUploadSessionFilters $filters): LengthAwarePaginator
    {
        if (! $this->centerScopeService->isSystemSuperAdmin($admin)) {
            $this->centerScopeService->assertAdminCenterId($admin, $center->id);
        }

        $query = VideoUploadSession::query()
            ->with(['videos'])
            ->where('center_id', $center->id)
            ->orderByDesc('created_at');

        $query = $this->applyFilters($query, $admin, $filters);

        return $query->paginate(
            $filters->perPage,
            ['*'],
            'page',
            $filters->page
        );
    }

    /**
     * @param  Builder<VideoUploadSession>  $query
     * @return Builder<VideoUploadSession>
     */
    private function applyFilters(Builder $query, User $admin, VideoUploadSessionFilters $filters): Builder
    {
        if ($filters->statusKey !== null) {
            $this->applyStatusKeyFilter($query, $filters->statusKey);
        }

        if ($filters->status !== null) {
            $query->where('upload_status', $filters->status);
        }

        if ($filters->centerId !== null) {
            $centerId = $filters->centerId;
            $this->centerScopeService->assertAdminCenterId($admin, $centerId);
            $query->where('center_id', $centerId);
        }

        return $query;
    }

    /**
     * @param  Builder<VideoUploadSession>  $query
     */
    private function applyStatusKeyFilter(Builder $query, string $statusKey): void
    {
        $normalized = strtolower(trim($statusKey));

        if ($normalized === '' || $normalized === 'all') {
            return;
        }

        if ($normalized === 'active') {
            $query->whereIn('upload_status', [
                VideoUploadStatus::Pending->value,
                VideoUploadStatus::Uploading->value,
                VideoUploadStatus::Processing->value,
            ]);

            return;
        }

        if ($normalized === 'terminal') {
            $query->whereIn('upload_status', [
                VideoUploadStatus::Ready->value,
                VideoUploadStatus::Failed->value,
            ]);

            return;
        }

        $enum = match ($normalized) {
            'pending' => VideoUploadStatus::Pending,
            'uploading' => VideoUploadStatus::Uploading,
            'processing' => VideoUploadStatus::Processing,
            'ready' => VideoUploadStatus::Ready,
            'failed' => VideoUploadStatus::Failed,
            default => null,
        };

        if ($enum instanceof VideoUploadStatus) {
            $query->where('upload_status', $enum->value);
        }
    }

    /**
     * @param  Builder<VideoUploadSession>  $query
     * @return Builder<VideoUploadSession>
     */
    private function applyScope(Builder $query, User $admin): Builder
    {
        if ($this->centerScopeService->isSystemSuperAdmin($admin)) {
            return $query;
        }

        $centerId = $this->centerScopeService->resolveAdminCenterId($admin);
        $this->centerScopeService->assertAdminCenterId($admin, $centerId);
        $query->where('center_id', $centerId);

        return $query;
    }
}
