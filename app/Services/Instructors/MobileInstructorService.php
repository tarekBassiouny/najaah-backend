<?php

declare(strict_types=1);

namespace App\Services\Instructors;

use App\Filters\Mobile\InstructorFilters;
use App\Models\Instructor;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class MobileInstructorService
{
    /**
     * @return LengthAwarePaginator<Instructor>
     */
    public function list(User $student, InstructorFilters $filters): LengthAwarePaginator
    {
        $query = Instructor::query()
            ->orderByDesc('created_at');

        if (is_numeric($student->center_id)) {
            $query->where('center_id', (int) $student->center_id);
        } else {
            $query->whereHas('center', function ($query): void {
                $query->where('type', 0);
            });
        }

        if ($filters->search !== null) {
            $term = $filters->search;
            $query->where(function ($query) use ($term): void {
                $query->where('name_translations', 'like', '%'.$term.'%')
                    ->orWhere('title_translations', 'like', '%'.$term.'%');
            });
        }

        return $query->paginate(
            $filters->perPage,
            ['*'],
            'page',
            $filters->page
        );
    }
}
