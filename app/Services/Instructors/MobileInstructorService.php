<?php

declare(strict_types=1);

namespace App\Services\Instructors;

use App\Filters\Mobile\InstructorFilters;
use App\Models\Center;
use App\Models\Instructor;
use App\Models\User;
use App\Services\Instructors\Contracts\MobileInstructorServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class MobileInstructorService implements MobileInstructorServiceInterface
{
    /**
     * @return LengthAwarePaginator<Instructor>
     */
    public function list(?User $student, InstructorFilters $filters): LengthAwarePaginator
    {
        $query = Instructor::query()
            ->orderByDesc('created_at');

        if ($student instanceof User) {
            $query->visibleToStudent($student);
        } else {
            // Guest user - show instructors from centers that allow guest browsing
            $query->whereHas('center', function ($query): void {
                $query->where('status', Center::STATUS_ACTIVE->value)
                    ->where('allow_guest_browsing', true);
            });
        }

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
