<?php

declare(strict_types=1);

namespace App\Services\Categories;

use App\Filters\Admin\CategoryFilters;
use App\Models\Category;
use App\Models\Center;
use App\Models\User;
use App\Services\Centers\CenterScopeService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AdminCategoryQueryService
{
    public function __construct(private readonly CenterScopeService $centerScopeService) {}

    /**
     * @return LengthAwarePaginator<Category>
     */
    public function paginate(User $admin, Center $center, CategoryFilters $filters): LengthAwarePaginator
    {
        $this->centerScopeService->assertAdminCenterId($admin, (int) $center->id);

        $query = Category::query()
            ->where('center_id', $center->id)
            ->orderBy('order_index')
            ->orderByDesc('created_at');

        if ($filters->isActive !== null) {
            $query->where('is_active', $filters->isActive);
        }

        if ($filters->parentId !== null) {
            $query->where('parent_id', $filters->parentId);
        }

        if ($filters->search !== null) {
            $query->whereTranslationLike(
                ['title'],
                $filters->search,
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
