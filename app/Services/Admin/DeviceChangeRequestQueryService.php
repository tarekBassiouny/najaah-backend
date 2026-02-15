<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Filters\Admin\DeviceChangeRequestFilters;
use App\Models\DeviceChangeRequest;
use App\Models\User;
use App\Services\Centers\CenterScopeService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class DeviceChangeRequestQueryService
{
    public function __construct(
        private readonly CenterScopeService $centerScopeService
    ) {}

    /**
     * @return Builder<DeviceChangeRequest>
     */
    public function build(User $admin, DeviceChangeRequestFilters $filters): Builder
    {
        $query = DeviceChangeRequest::query()->with(['user', 'center', 'decider']);

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
     * @return Builder<DeviceChangeRequest>
     */
    public function buildForCenter(User $admin, int $centerId, DeviceChangeRequestFilters $filters): Builder
    {
        $this->centerScopeService->assertAdminCenterId($admin, $centerId);

        $query = DeviceChangeRequest::query()
            ->with(['user', 'center', 'decider'])
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
     * @return LengthAwarePaginator<DeviceChangeRequest>
     */
    public function paginate(User $admin, DeviceChangeRequestFilters $filters): LengthAwarePaginator
    {
        return $this->build($admin, $filters)->paginate(
            $filters->perPage,
            ['*'],
            'page',
            $filters->page
        );
    }

    /**
     * @return LengthAwarePaginator<DeviceChangeRequest>
     */
    public function paginateForCenter(User $admin, int $centerId, DeviceChangeRequestFilters $filters): LengthAwarePaginator
    {
        return $this->buildForCenter($admin, $centerId, $filters)->paginate(
            $filters->perPage,
            ['*'],
            'page',
            $filters->page
        );
    }
}
