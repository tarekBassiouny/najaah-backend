<?php

declare(strict_types=1);

namespace App\Services\Categories;

use App\Enums\CenterType;
use App\Exceptions\NotFoundException;
use App\Filters\Mobile\CategoryFilters;
use App\Models\Category;
use App\Models\Center;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class MobileCategoryService
{
    /**
     * @return LengthAwarePaginator<Category>
     */
    public function list(?User $student, CategoryFilters $filters): LengthAwarePaginator
    {
        $query = Category::query()
            ->where('is_active', true)
            ->orderByDesc('created_at');

        if ($student instanceof User) {
            $query->visibleToStudent($student);
        } else {
            // Guest user - show categories from centers that allow guest browsing
            $query->where(function ($query): void {
                $query->whereNull('center_id')
                    ->orWhereHas('center', function ($query): void {
                        $query->where('status', Center::STATUS_ACTIVE->value)
                            ->where('allow_guest_browsing', true);
                    });
            });
        }

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

    /**
     * @return LengthAwarePaginator<Category>
     */
    public function listForCenter(Center $center, CategoryFilters $filters): LengthAwarePaginator
    {
        if ($center->type !== CenterType::Unbranded || $center->status !== Center::STATUS_ACTIVE) {
            throw new NotFoundException('Center not found.', 404);
        }

        $query = Category::query()
            ->where('center_id', $center->id)
            ->where('is_active', true)
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
