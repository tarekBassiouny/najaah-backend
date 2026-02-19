<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Filters\Admin\ExtraViewRequestFilters;
use App\Models\ExtraViewRequest;
use App\Models\User;
use App\Services\Centers\CenterScopeService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class ExtraViewRequestQueryService
{
    public function __construct(
        private readonly CenterScopeService $centerScopeService
    ) {}

    /**
     * @return Builder<ExtraViewRequest>
     */
    public function build(User $admin, ExtraViewRequestFilters $filters): Builder
    {
        $query = ExtraViewRequest::query()->with(['user', 'center', 'video', 'course', 'decider']);

        if ($filters->status !== null) {
            $query->where('status', $filters->status);
        }

        if ($filters->userId !== null) {
            $query->where('user_id', $filters->userId);
        }

        if ($filters->dateFrom !== null) {
            $query->where('created_at', '>=', Carbon::parse($filters->dateFrom)->startOfDay());
        }

        if ($filters->dateTo !== null) {
            $query->where('created_at', '<=', Carbon::parse($filters->dateTo)->endOfDay());
        }

        if ($this->centerScopeService->isSystemSuperAdmin($admin)) {
            if ($filters->centerId !== null) {
                $query->where('center_id', $filters->centerId);
            }
        } else {
            $centerId = $this->centerScopeService->resolveAdminCenterId($admin);
            $this->centerScopeService->assertAdminCenterId($admin, $centerId);
            $query->where('center_id', (int) $centerId);
        }

        return $query->orderByDesc('created_at');
    }

    /**
     * @return Builder<ExtraViewRequest>
     */
    public function buildForCenter(User $admin, int $centerId, ExtraViewRequestFilters $filters): Builder
    {
        $this->centerScopeService->assertAdminCenterId($admin, $centerId);

        $query = ExtraViewRequest::query()
            ->with(['user', 'center', 'video', 'course', 'decider'])
            ->where('center_id', $centerId);

        if ($filters->status !== null) {
            $query->where('status', $filters->status);
        }

        if ($filters->userId !== null) {
            $query->where('user_id', $filters->userId);
        }

        if ($filters->dateFrom !== null) {
            $query->where('created_at', '>=', Carbon::parse($filters->dateFrom)->startOfDay());
        }

        if ($filters->dateTo !== null) {
            $query->where('created_at', '<=', Carbon::parse($filters->dateTo)->endOfDay());
        }

        return $query->orderByDesc('created_at');
    }

    /**
     * @return LengthAwarePaginator<ExtraViewRequest>
     */
    public function paginate(User $admin, ExtraViewRequestFilters $filters): LengthAwarePaginator
    {
        return $this->build($admin, $filters)->paginate(
            $filters->perPage,
            ['*'],
            'page',
            $filters->page
        );
    }

    /**
     * @return LengthAwarePaginator<ExtraViewRequest>
     */
    public function paginateForCenter(User $admin, int $centerId, ExtraViewRequestFilters $filters): LengthAwarePaginator
    {
        return $this->buildForCenter($admin, $centerId, $filters)->paginate(
            $filters->perPage,
            ['*'],
            'page',
            $filters->page
        );
    }
}
