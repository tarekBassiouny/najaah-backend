<?php

declare(strict_types=1);

namespace App\Services\Categories;

use App\Filters\Mobile\CategoryFilters;
use App\Models\Category;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class MobileCategoryService
{
    /**
     * @return LengthAwarePaginator<Category>
     */
    public function list(User $student, CategoryFilters $filters): LengthAwarePaginator
    {
        $query = Category::query()
            ->where('is_active', true)
            ->visibleToStudent($student)
            ->orderByDesc('created_at');

        if ($filters->search !== null) {
            $term = $filters->search;
            $query->whereTranslationLike(
                ['title'],
                $term,
                ['en', 'ar']
            );
        }

        return $query->paginate(
            $filters->perPage,
            ['*'],
            'page',
            $filters->page
        );
    }
}
