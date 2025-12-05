<?php

declare(strict_types=1);

namespace App\Actions\Instructors;

use App\Models\Instructor;
use App\Services\InstructorService;

class UpdateInstructorAction
{
    public function __construct(
        private readonly InstructorService $instructorService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(Instructor $instructor, array $data): Instructor
    {
        return $this->instructorService->update($instructor, $data);
    }
}
