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
        if (! $this->centerScopeService->isSystemSuperAdmin($admin)) {
            $this->centerScopeService->assertAdminCenterId($admin, $center->id);
        }

        $query = Pdf::query()
            ->where('center_id', $center->id)
            ->with(['creator', 'uploadSession'])
            ->withCount(['courses', 'sections'])
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

        if ($filters->status !== null) {
            $status = $filters->status;
            $query->whereHas('uploadSession', static function (Builder $builder) use ($status): void {
                $builder->where('upload_status', $status);
            });
        }

        if ($filters->sourceType !== null) {
            $query->where('source_type', $filters->sourceType);
        }

        if ($filters->sourceProvider !== null) {
            $query->where('source_provider', strtolower($filters->sourceProvider));
        }

        if ($filters->createdFrom !== null) {
            $query->whereDate('created_at', '>=', $filters->createdFrom);
        }

        if ($filters->createdTo !== null) {
            $query->whereDate('created_at', '<=', $filters->createdTo);
        }

        if ($filters->query !== null) {
            $search = '%'.$filters->query.'%';
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('title_translations->en', 'like', $search)
                    ->orWhere('title_translations->ar', 'like', $search)
                    ->orWhere('description_translations->en', 'like', $search)
                    ->orWhere('description_translations->ar', 'like', $search)
                    ->orWhere('source_id', 'like', $search);
            });
        }

        if ($filters->query === null && $filters->search !== null) {
            $search = '%'.$filters->search.'%';
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('title_translations->en', 'like', $search)
                    ->orWhere('title_translations->ar', 'like', $search);
            });
        }

        return $query;
    }
}
