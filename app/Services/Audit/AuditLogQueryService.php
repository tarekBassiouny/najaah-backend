<?php

declare(strict_types=1);

namespace App\Services\Audit;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\Centers\CenterScopeService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class AuditLogQueryService
{
    public function __construct(
        private readonly CenterScopeService $centerScopeService
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<AuditLog>
     */
    public function paginate(User $admin, int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = AuditLog::query()
            ->with('user')
            ->orderByDesc('created_at');

        $query = $this->applyScope($query, $admin);
        if ($admin->hasRole('super_admin') && isset($filters['center_id']) && is_numeric($filters['center_id'])) {
            $centerId = (int) $filters['center_id'];
            $query->whereHas('user', static function (Builder $builder) use ($centerId): void {
                $builder->where('center_id', $centerId);
            });
        }

        $query = $this->applyFilters($query, $filters);

        return $query->paginate($perPage);
    }

    /**
     * @param  Builder<AuditLog>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<AuditLog>
     */
    private function applyFilters(Builder $query, array $filters): Builder
    {
        if (isset($filters['entity_type']) && is_string($filters['entity_type'])) {
            $query->where('entity_type', $filters['entity_type']);
        }

        if (isset($filters['entity_id']) && is_numeric($filters['entity_id'])) {
            $query->where('entity_id', (int) $filters['entity_id']);
        }

        if (isset($filters['action']) && is_string($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        if (isset($filters['user_id']) && is_numeric($filters['user_id'])) {
            $query->where('user_id', (int) $filters['user_id']);
        }

        if (isset($filters['date_from']) && is_string($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to']) && is_string($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
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
