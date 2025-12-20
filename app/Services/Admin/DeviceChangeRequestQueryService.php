<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\DeviceChangeRequest;
use App\Models\User;
use App\Services\Centers\CenterScopeService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class DeviceChangeRequestQueryService
{
    public function __construct(
        private readonly CenterScopeService $centerScopeService
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<DeviceChangeRequest>
     */
    public function build(User $admin, array $filters): Builder
    {
        $query = DeviceChangeRequest::query();

        $status = $filters['status'] ?? null;
        if (is_string($status) && $status !== '') {
            $query->where('status', $status);
        }

        if (isset($filters['user_id']) && is_numeric($filters['user_id'])) {
            $query->where('user_id', (int) $filters['user_id']);
        }

        $dateFrom = $filters['date_from'] ?? null;
        if (is_string($dateFrom) && $dateFrom !== '') {
            $query->where('created_at', '>=', Carbon::parse($dateFrom)->startOfDay());
        }

        $dateTo = $filters['date_to'] ?? null;
        if (is_string($dateTo) && $dateTo !== '') {
            $query->where('created_at', '<=', Carbon::parse($dateTo)->endOfDay());
        }

        if ($admin->hasRole('super_admin')) {
            if (isset($filters['center_id']) && is_numeric($filters['center_id'])) {
                $query->where('center_id', (int) $filters['center_id']);
            }
        } else {
            $centerId = $admin->center_id;
            $this->centerScopeService->assertAdminCenterId($admin, is_numeric($centerId) ? (int) $centerId : null);
            $query->where('center_id', (int) $centerId);
        }

        return $query->orderByDesc('created_at');
    }
}
