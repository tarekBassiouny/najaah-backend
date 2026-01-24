<?php

declare(strict_types=1);

namespace App\Services\Pdfs;

use App\Models\Center;
use App\Models\Pdf;
use App\Models\User;
use App\Services\Centers\CenterScopeService;
use App\Services\Pdfs\Contracts\AdminPdfQueryServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class AdminPdfQueryService implements AdminPdfQueryServiceInterface
{
    public function __construct(private readonly CenterScopeService $centerScopeService) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<Pdf>
     */
    public function paginateForCenter(User $admin, Center $center, int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        if (! $admin->hasRole('super_admin')) {
            $this->centerScopeService->assertAdminCenterId($admin, $center->id);
        }

        $query = Pdf::query()
            ->where('center_id', $center->id)
            ->with(['creator'])
            ->orderByDesc('created_at');

        $query = $this->applyFilters($query, $filters);

        return $query->paginate($perPage);
    }

    /**
     * @param  Builder<Pdf>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<Pdf>
     */
    private function applyFilters(Builder $query, array $filters): Builder
    {
        if (isset($filters['course_id']) && is_numeric($filters['course_id'])) {
            $courseId = (int) $filters['course_id'];
            $query->whereHas('courses', static function (Builder $builder) use ($courseId): void {
                $builder->where('courses.id', $courseId);
            });
        }

        if (isset($filters['search']) && is_string($filters['search'])) {
            $term = trim($filters['search']);
            if ($term !== '') {
                // Search targets the stored base string; not locale-aware yet.
                $query->where('title_translations', 'like', '%'.$term.'%');
            }
        }

        return $query;
    }
}
