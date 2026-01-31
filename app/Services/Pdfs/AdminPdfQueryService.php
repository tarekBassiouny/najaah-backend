<?php

declare(strict_types=1);

namespace App\Services\Pdfs;

use App\Filters\Admin\PdfFilters;
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
     * @return LengthAwarePaginator<Pdf>
     */
    public function paginateForCenter(User $admin, Center $center, PdfFilters $filters): LengthAwarePaginator
    {
        if (! $admin->hasRole('super_admin')) {
            $this->centerScopeService->assertAdminCenterId($admin, $center->id);
        }

        $query = Pdf::query()
            ->where('center_id', $center->id)
            ->with(['creator'])
            ->orderByDesc('created_at');

        $query = $this->applyFilters($query, $filters);

        return $query->paginate(
            $filters->perPage,
            ['*'],
            'page',
            $filters->page
        );
    }

    /**
     * @param  Builder<Pdf>  $query
     * @return Builder<Pdf>
     */
    private function applyFilters(Builder $query, PdfFilters $filters): Builder
    {
        if ($filters->courseId !== null) {
            $courseId = $filters->courseId;
            $query->whereHas('courses', static function (Builder $builder) use ($courseId): void {
                $builder->where('courses.id', $courseId);
            });
        }

        if ($filters->search !== null) {
            $query->whereTranslationLike(
                ['title'],
                $filters->search,
                ['en', 'ar']
            );
        }

        return $query;
    }
}
