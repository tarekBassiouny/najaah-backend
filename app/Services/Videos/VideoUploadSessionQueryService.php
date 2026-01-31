<?php

declare(strict_types=1);

namespace App\Services\Videos;

use App\Filters\Admin\VideoUploadSessionFilters;
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
     * @param  Builder<VideoUploadSession>  $query
     * @return Builder<VideoUploadSession>
     */
    private function applyFilters(Builder $query, User $admin, VideoUploadSessionFilters $filters): Builder
    {
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
     * @return Builder<VideoUploadSession>
     */
    private function applyScope(Builder $query, User $admin): Builder
    {
        if ($admin->hasRole('super_admin')) {
            return $query;
        }

        $this->centerScopeService->assertAdminCenterId($admin, $admin->center_id);
        $query->where('center_id', $admin->center_id);

        return $query;
    }
}
