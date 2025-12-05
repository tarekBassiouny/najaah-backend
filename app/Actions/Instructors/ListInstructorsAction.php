<?php

declare(strict_types=1);

namespace App\Actions\Instructors;

use App\Services\InstructorService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListInstructorsAction
{
    public function __construct(
        private readonly InstructorService $instructorService,
    ) {}

    /**
     * @return LengthAwarePaginator<\App\Models\Instructor>
     */
    public function execute(int $perPage): LengthAwarePaginator
    {
        return $this->instructorService->paginate($perPage);
    }
}
