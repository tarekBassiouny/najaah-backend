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
            ->orderByDesc('created_at');

        if (is_numeric($student->center_id)) {
            $query->where('center_id', (int) $student->center_id);
        } else {
            $query->whereNull('center_id')
                ->orWhereHas('center', function ($query): void {
                    $query->where('type', 0);
                });
        }

        if ($filters->search !== null) {
            $term = $filters->search;
            $query->where('title_translations', 'like', '%'.$term.'%');
        }

        return $query->paginate(
            $filters->perPage,
            ['*'],
            'page',
            $filters->page
        );
    }
}
