<?php

declare(strict_types=1);

namespace App\Services\Sections;

use App\Filters\Admin\SectionFilters;
use App\Models\Section;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class SectionQueryService
{
    /**
     * @return LengthAwarePaginator<Section>
     */
    public function paginateForCourse(int $courseId, SectionFilters $filters): LengthAwarePaginator
    {
        $query = Section::query()
            ->where('course_id', $courseId)
            ->withCount(['videos', 'pdfs'])
            ->orderBy('order_index');

        $this->applyFilters($query, $filters);

        return $query->paginate(
            $filters->perPage,
            ['*'],
            'page',
            $filters->page
        );
    }

    /**
     * @param  Builder<Section>  $query
     */
    private function applyFilters(Builder $query, SectionFilters $filters): void
    {
        if ($filters->search !== null) {
            $query->whereTranslationLike(['title'], $filters->search, ['en', 'ar']);
        }

        if ($filters->isPublished !== null) {
            $query->where('visible', $filters->isPublished);
        }
    }
}
