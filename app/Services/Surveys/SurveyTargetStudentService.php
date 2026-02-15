<?php

declare(strict_types=1);

namespace App\Services\Surveys;

use App\Enums\CenterType;
use App\Enums\SurveyScopeType;
use App\Models\Center;
use App\Models\User;
use App\Services\Centers\CenterScopeService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class SurveyTargetStudentService
{
    public function __construct(
        private readonly CenterScopeService $centerScopeService
    ) {}

    /**
     * @return LengthAwarePaginator<User>
     */
    public function paginate(
        User $actor,
        SurveyScopeType $scopeType,
        ?int $centerId,
        ?int $status,
        ?string $search,
        int $perPage,
        int $page
    ): LengthAwarePaginator {
        $query = User::query()
            ->with('center')
            ->where('is_student', true)
            ->orderByDesc('created_at');

        if ($status !== null) {
            $query->where('status', $status);
        }

        if ($search !== null) {
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('name', 'like', '%'.$search.'%')
                    ->orWhere('username', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%')
                    ->orWhere('phone', 'like', '%'.$search.'%');
            });
        }

        if ($scopeType === SurveyScopeType::System) {
            if (! $this->centerScopeService->isSystemSuperAdmin($actor)) {
                throw new \InvalidArgumentException('Only system super admins can target system survey students.');
            }

            $query->where(function (Builder $builder): void {
                $builder->whereNull('center_id')
                    ->orWhereIn('center_id', Center::query()
                        ->select('id')
                        ->where('type', CenterType::Unbranded->value));
            });

            if ($centerId !== null) {
                $query->where('center_id', $centerId);
            }
        } else {
            $this->centerScopeService->assertAdminCenterId($actor, $centerId);
            $query->where('center_id', $centerId);
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
    }
}
