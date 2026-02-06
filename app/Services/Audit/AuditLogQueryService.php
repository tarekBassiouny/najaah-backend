<?php

declare(strict_types=1);

namespace App\Services\Audit;

use App\Filters\Admin\AuditLogFilters;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\Centers\CenterScopeService;
use App\Support\AuditActions;
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
            $query = $this->applyCenterFilter($query, $filters->centerId);
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

        if ($filters->courseId !== null) {
            $query->where('course_id', $filters->courseId);
        }

        if ($filters->action !== null) {
            $query = $this->applyActionFilter($query, $filters->action);
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
    private function applyActionFilter(Builder $query, string $action): Builder
    {
        $normalized = strtolower($action);

        return match ($normalized) {
            'create' => $this->applyActionSuffixFilter($query, ['_created', '_added', '_assigned']),
            'update' => $this->applyActionSuffixFilter($query, ['_updated', '_edited', '_synced', '_reordered', '_toggled']),
            'delete' => $this->applyActionSuffixFilter($query, ['_deleted', '_removed', '_detached', '_revoked']),
            'login' => $query->whereIn('action', [AuditActions::ADMIN_LOGIN, AuditActions::STUDENT_LOGIN]),
            'logout' => $query->whereIn('action', [AuditActions::ADMIN_LOGOUT, AuditActions::STUDENT_LOGOUT]),
            default => $query->where('action', $action),
        };
    }

    /**
     * @param  Builder<AuditLog>  $query
     * @param  array<int, string>  $suffixes
     * @return Builder<AuditLog>
     */
    private function applyActionSuffixFilter(Builder $query, array $suffixes): Builder
    {
        return $query->where(static function (Builder $builder) use ($suffixes): void {
            foreach ($suffixes as $suffix) {
                $builder->orWhere('action', 'like', '%'.$suffix);
            }
        });
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

        return $this->applyCenterFilter($query, (int) $centerId);
    }

    /**
     * @param  Builder<AuditLog>  $query
     * @return Builder<AuditLog>
     */
    private function applyCenterFilter(Builder $query, int $centerId): Builder
    {
        return $query->where(static function (Builder $builder) use ($centerId): void {
            $builder->where('center_id', $centerId)
                ->orWhere(static function (Builder $fallback) use ($centerId): void {
                    $fallback->whereNull('center_id')
                        ->whereHas('user', static function (Builder $userQuery) use ($centerId): void {
                            $userQuery->where('center_id', $centerId);
                        });
                });
        });
    }
}
