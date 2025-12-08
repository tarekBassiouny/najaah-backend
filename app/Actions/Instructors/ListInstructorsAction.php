<?php

declare(strict_types=1);

namespace App\Actions\Instructors;

use App\Services\Instructors\Contracts\InstructorServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListInstructorsAction
{
    public function __construct(
        private readonly InstructorServiceInterface $instructorService,
    ) {}

    /**
     * @return LengthAwarePaginator<\App\Models\Instructor>
     */
    public function execute(int $perPage): LengthAwarePaginator
    {
        return $this->instructorService->paginate($perPage);
    }
}
