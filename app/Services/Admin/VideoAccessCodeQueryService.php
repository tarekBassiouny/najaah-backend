<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Filters\Admin\VideoAccessCodeFilters;
use App\Models\User;
use App\Models\VideoAccessCode;
use App\Services\Centers\CenterScopeService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class VideoAccessCodeQueryService
{
    public function __construct(
        private readonly CenterScopeService $centerScopeService
    ) {}

    /**
     * @return Builder<VideoAccessCode>
     */
    public function buildForCenter(User $admin, int $centerId, VideoAccessCodeFilters $filters): Builder
    {
        if (! $this->isSystemScopedAdmin($admin)) {
            $this->centerScopeService->assertAdminCenterId($admin, $centerId);
        }

        $query = VideoAccessCode::query()
            ->with(['user', 'video', 'course', 'request', 'generator', 'revoker'])
            ->where('center_id', $centerId);

        $this->applyFilters($query, $filters);

        return $query->orderByDesc('created_at');
    }

    /**
     * @return LengthAwarePaginator<VideoAccessCode>
     */
    public function paginateForCenter(User $admin, int $centerId, VideoAccessCodeFilters $filters): LengthAwarePaginator
    {
        return $this->buildForCenter($admin, $centerId, $filters)->paginate(
            $filters->perPage,
            ['*'],
            'page',
            $filters->page
        );
    }

    /**
     * @param  Builder<VideoAccessCode>  $query
     */
    private function applyFilters(Builder $query, VideoAccessCodeFilters $filters): void
    {
        if ($filters->status !== null) {
            $query->where('status', $filters->status);
        }

        if ($filters->userId !== null) {
            $query->where('user_id', $filters->userId);
        }

        if ($filters->videoId !== null) {
            $query->where('video_id', $filters->videoId);
        }

        if ($filters->courseId !== null) {
            $query->where('course_id', $filters->courseId);
        }

        if ($filters->search !== null) {
            $term = trim($filters->search);

            if ($term !== '') {
                $query->where(function (Builder $wrapped) use ($term): void {
                    $wrapped->where('code', 'like', sprintf('%%%s%%', $term))
                        ->orWhereHas('user', static function (Builder $userQuery) use ($term): void {
                            $userQuery
                                ->where('name', 'like', sprintf('%%%s%%', $term))
                                ->orWhere('email', 'like', sprintf('%%%s%%', $term))
                                ->orWhere('phone', 'like', sprintf('%%%s%%', $term));
                        });
                });
            }
        }

        if ($filters->dateFrom !== null) {
            $query->where('created_at', '>=', Carbon::parse($filters->dateFrom)->startOfDay());
        }

        if ($filters->dateTo !== null) {
            $query->where('created_at', '<=', Carbon::parse($filters->dateTo)->endOfDay());
        }
    }

    private function isSystemScopedAdmin(User $admin): bool
    {
        return ! $admin->is_student && ! is_numeric($admin->center_id);
    }
}
