<?php

declare(strict_types=1);

namespace App\Services\Instructors\Contracts;

use App\Filters\Mobile\InstructorFilters;
use App\Models\Instructor;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface MobileInstructorServiceInterface
{
    /**
     * @return LengthAwarePaginator<Instructor>
     */
    public function list(User $student, InstructorFilters $filters): LengthAwarePaginator;
}
