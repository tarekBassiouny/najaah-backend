<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Enums\UserDeviceStatus;
use App\Filters\Admin\StudentFilters;
use App\Models\User;
use App\Services\Centers\CenterScopeService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class StudentQueryService
{
    public function __construct(
        private readonly CenterScopeService $centerScopeService
    ) {}

    /**
     * @return Builder<User>
     */
    public function build(User $admin, StudentFilters $filters): Builder
    {
        $query = User::query()
            ->with('center')
            ->with([
                'devices' => static function ($relation): void {
                    $relation
                        ->where('status', UserDeviceStatus::Active->value)
                        ->orderByDesc('last_used_at')
                        ->orderByDesc('id');
                },
            ])
            ->where('is_student', true)
            ->orderByDesc('created_at');

        if ($filters->status !== null) {
            $query->where('status', $filters->status);
        }

        if ($filters->search !== null) {
            $term = $filters->search;
            $query->where(static function (Builder $builder) use ($term): void {
                $builder->where('name', 'like', '%'.$term.'%')
                    ->orWhere('username', 'like', '%'.$term.'%')
                    ->orWhere('email', 'like', '%'.$term.'%')
                    ->orWhere('phone', 'like', '%'.$term.'%');
            });
        }

        if ($admin->hasRole('super_admin')) {
            if ($filters->centerId !== null) {
                $query->where('center_id', $filters->centerId);
            }
        } else {
            $centerId = $admin->center_id;
            $this->centerScopeService->assertAdminCenterId($admin, is_numeric($centerId) ? (int) $centerId : null);
            $query->where('center_id', (int) $centerId);
        }

        return $query;
    }

    /**
     * @return LengthAwarePaginator<User>
     */
    public function paginate(User $admin, StudentFilters $filters): LengthAwarePaginator
    {
        return $this->build($admin, $filters)->paginate(
            $filters->perPage,
            ['*'],
            'page',
            $filters->page
        );
    }
}
