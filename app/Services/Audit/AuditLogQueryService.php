<?php

declare(strict_types=1);

namespace App\Services\Audit;

use App\Filters\Admin\AuditLogFilters;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\Centers\CenterScopeService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class AuditLogQueryService
{
    public function __construct(
        private readonly CenterScopeService $centerScopeService
    ) {}

    /**
     * @return LengthAwarePaginator<AuditLog>
     */
    public function paginate(User $admin, AuditLogFilters $filters): LengthAwarePaginator
    {
        $query = AuditLog::query()
            ->with('user')
            ->orderByDesc('created_at');

        $query = $this->applyScope($query, $admin);
        if ($admin->hasRole('super_admin') && $filters->centerId !== null) {
            $centerId = $filters->centerId;
            $query->whereHas('user', static function (Builder $builder) use ($centerId): void {
                $builder->where('center_id', $centerId);
            });
        }

        $query = $this->applyFilters($query, $filters);

        return $query->paginate(
            $filters->perPage,
            ['*'],
            'page',
            $filters->page
        );
    }

    /**
     * @param  Builder<AuditLog>  $query
     * @return Builder<AuditLog>
     */
    private function applyFilters(Builder $query, AuditLogFilters $filters): Builder
    {
        if ($filters->entityType !== null) {
            $query->where('entity_type', $filters->entityType);
        }

        if ($filters->entityId !== null) {
            $query->where('entity_id', $filters->entityId);
        }

        if ($filters->action !== null) {
            $query->where('action', $filters->action);
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

        return $query;
    }

    /**
     * @param  Builder<AuditLog>  $query
     * @return Builder<AuditLog>
     */
    private function applyScope(Builder $query, User $admin): Builder
    {
        if ($admin->hasRole('super_admin')) {
            return $query;
        }

        $centerId = $admin->center_id;
        $this->centerScopeService->assertAdminCenterId($admin, is_numeric($centerId) ? (int) $centerId : null);

        return $query->whereHas('user', static function (Builder $builder) use ($centerId): void {
            $builder->where('center_id', (int) $centerId);
        });
    }
}
