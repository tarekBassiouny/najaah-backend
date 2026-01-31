<?php

declare(strict_types=1);

namespace App\Services\Instructors;

use App\Filters\Mobile\InstructorFilters;
use App\Models\Instructor;
use App\Models\User;
use App\Services\Instructors\Contracts\MobileInstructorServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class MobileInstructorService implements MobileInstructorServiceInterface
{
    /**
     * @return LengthAwarePaginator<Instructor>
     */
    public function list(User $student, InstructorFilters $filters): LengthAwarePaginator
    {
        $query = Instructor::query()
            ->visibleToStudent($student)
            ->orderByDesc('created_at');

        if ($filters->search !== null) {
            $query->whereTranslationLike(
                ['name', 'title'],
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
