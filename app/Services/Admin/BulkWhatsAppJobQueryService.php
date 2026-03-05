<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Filters\Admin\BulkWhatsAppJobFilters;
use App\Models\BulkWhatsAppJob;
use App\Models\User;
use App\Services\Centers\CenterScopeService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class BulkWhatsAppJobQueryService
{
    public function __construct(
        private readonly CenterScopeService $centerScopeService
    ) {}

    /**
     * @return Builder<BulkWhatsAppJob>
     */
    public function buildForCenter(User $admin, int $centerId, BulkWhatsAppJobFilters $filters): Builder
    {
        if (! $this->isSystemScopedAdmin($admin)) {
            $this->centerScopeService->assertAdminCenterId($admin, $centerId);
        }

        $query = BulkWhatsAppJob::query()
            ->with(['creator'])
            ->where('center_id', $centerId);

        if ($filters->status !== null) {
            $query->where('status', $filters->status);
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
     * @return LengthAwarePaginator<BulkWhatsAppJob>
     */
    public function paginateForCenter(User $admin, int $centerId, BulkWhatsAppJobFilters $filters): LengthAwarePaginator
    {
        return $this->buildForCenter($admin, $centerId, $filters)->paginate(
            $filters->perPage,
            ['*'],
            'page',
            $filters->page
        );
    }

    private function isSystemScopedAdmin(User $admin): bool
    {
        return ! $admin->is_student && ! is_numeric($admin->center_id);
    }
}
