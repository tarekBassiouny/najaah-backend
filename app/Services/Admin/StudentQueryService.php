<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Enums\CenterType;
use App\Enums\UserDeviceStatus;
use App\Filters\Admin\StudentFilters;
use App\Models\User;
use App\Services\Centers\CenterScopeService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

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
            ->with(['center', 'grade', 'school', 'college'])
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

        $this->applyFilters($query, $filters);

        $this->applyCenterTypeFilter($query, $filters);

        if ($this->centerScopeService->isSystemSuperAdmin($admin)) {
            if ($filters->centerId !== null) {
                // Use user_centers pivot for center-specific queries
                $this->applyCenterAssociationFilter($query, $filters->centerId);
            }
        } else {
            $centerId = $this->centerScopeService->resolveAdminCenterId($admin);
            $this->centerScopeService->assertAdminCenterId($admin, $centerId);
            // Use user_centers pivot for center-specific queries
            $this->applyCenterAssociationFilter($query, (int) $centerId);
        }

        return $query;
    }

    /**
     * @return Builder<User>
     */
    public function buildForCenter(User $admin, int $centerId, StudentFilters $filters): Builder
    {
        $this->centerScopeService->assertAdminCenterId($admin, $centerId);

        $query = User::query()
            ->with(['center', 'grade', 'school', 'college'])
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

        // Use user_centers pivot to filter students associated with this center
        $this->applyCenterAssociationFilter($query, $centerId);

        $this->applyFilters($query, $filters);

        $this->applyCenterTypeFilter($query, $filters);

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

    /**
     * @return LengthAwarePaginator<User>
     */
    public function paginateForCenter(User $admin, int $centerId, StudentFilters $filters): LengthAwarePaginator
    {
        return $this->buildForCenter($admin, $centerId, $filters)->paginate(
            $filters->perPage,
            ['*'],
            'page',
            $filters->page
        );
    }

    /**
     * Filter students by center association via user_centers pivot.
     *
     * Students appear in a center's list only if they have an entry in user_centers,
     * which is created either:
     * - When an admin adds a student through center scope (via StudentService::createForCenter)
     * - When any student enrolls in a course (via EnrollmentObserver)
     *
     * @param  Builder<User>  $query
     */
    private function applyCenterAssociationFilter(Builder $query, int $centerId): void
    {
        $query->whereHas('centers', static function ($centerQuery) use ($centerId): void {
            $centerQuery->where('centers.id', $centerId)
                ->where('user_centers.type', 'student');
        });
    }

    /**
     * @param  Builder<User>  $query
     */
    private function applyCenterTypeFilter(Builder $query, StudentFilters $filters): void
    {
        if ($filters->centerType === null) {
            return;
        }

        if ($filters->centerType === CenterType::Unbranded->value) {
            $query->whereNull('center_id');

            return;
        }

        $query->whereNotNull('center_id');
    }

    /**
     * @param  Builder<User>  $query
     */
    private function applyFilters(Builder $query, StudentFilters $filters): void
    {
        if ($filters->status !== null) {
            $query->where('status', $filters->status);
        }

        if ($filters->search !== null) {
            $term = trim($filters->search);
            if ($term !== '') {
                $query->where(function (Builder $builder) use ($term): void {
                    $builder->where('name', 'like', '%'.$term.'%')
                        ->orWhere('username', 'like', '%'.$term.'%')
                        ->orWhere('email', 'like', '%'.$term.'%')
                        ->orWhere('phone', 'like', '%'.$term.'%');

                    $this->appendPhoneSearch($builder, $this->phoneSearchTerms($term));
                });
            }
        }

        if ($filters->studentName !== null) {
            $studentName = trim($filters->studentName);
            if ($studentName !== '') {
                $query->where('name', 'like', '%'.$studentName.'%');
            }
        }

        if ($filters->studentPhone !== null) {
            $studentPhone = trim($filters->studentPhone);
            if ($studentPhone !== '') {
                $query->where(function (Builder $builder) use ($studentPhone): void {
                    $builder->where('phone', 'like', '%'.$studentPhone.'%');
                    $this->appendPhoneSearch($builder, $this->phoneSearchTerms($studentPhone));
                });
            }
        }

        if ($filters->studentEmail !== null) {
            $studentEmail = trim($filters->studentEmail);
            if ($studentEmail !== '') {
                $query->where('email', 'like', '%'.$studentEmail.'%');
            }
        }

        if ($filters->gradeId !== null) {
            $query->where('grade_id', $filters->gradeId);
        }

        if ($filters->schoolId !== null) {
            $query->where('school_id', $filters->schoolId);
        }

        if ($filters->collegeId !== null) {
            $query->where('college_id', $filters->collegeId);
        }

        if ($filters->stage !== null) {
            $query->whereHas('grade', static function (Builder $gradeQuery) use ($filters): void {
                $gradeQuery->where('stage', $filters->stage);
            });
        }
    }

    /**
     * @phpstan-param Builder<User> $builder
     * @phpstan-param string[] $values
     */
    private function appendPhoneSearch(Builder $builder, array $values): void
    {
        foreach ($values as $value) {
            if ($value === '') {
                continue;
            }

            $builder->orWhere('phone', 'like', '%'.$value.'%')
                ->orWhere(DB::raw("CONCAT(REPLACE(country_code, '+', ''), phone)"), 'like', '%'.$value.'%');
        }
    }

    /**
     * @phpstan-return string[]
     */
    private function phoneSearchTerms(string $term): array
    {
        $digits = preg_replace('/\D+/', '', $term) ?: '';
        if ($digits === '') {
            return [];
        }

        $terms = [$digits];

        if (str_starts_with($digits, '00')) {
            $terms[] = ltrim($digits, '0');
        }

        if (str_starts_with($digits, '0')) {
            $terms[] = ltrim($digits, '0');
        }

        return array_values(array_unique($terms));
    }
}
