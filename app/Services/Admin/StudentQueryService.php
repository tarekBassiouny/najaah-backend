<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\User;
use App\Services\Centers\CenterScopeService;
use Illuminate\Database\Eloquent\Builder;

class StudentQueryService
{
    public function __construct(
        private readonly CenterScopeService $centerScopeService
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<User>
     */
    public function build(User $admin, array $filters): Builder
    {
        $query = User::query()
            ->where('is_student', true)
            ->orderByDesc('created_at');

        if (isset($filters['status']) && is_numeric($filters['status'])) {
            $query->where('status', (int) $filters['status']);
        }

        if (isset($filters['search']) && is_string($filters['search'])) {
            $term = trim($filters['search']);
            if ($term !== '') {
                $query->where(static function (Builder $builder) use ($term): void {
                    $builder->where('name', 'like', '%'.$term.'%')
                        ->orWhere('username', 'like', '%'.$term.'%')
                        ->orWhere('email', 'like', '%'.$term.'%');
                });
            }
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

        return $query;
    }
}
