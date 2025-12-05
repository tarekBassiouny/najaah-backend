<?php

declare(strict_types=1);

namespace App\Actions\Instructors;

use App\Models\Instructor;
use App\Services\InstructorService;

class DeleteInstructorAction
{
    public function __construct(private readonly InstructorService $instructorService) {}

    public function execute(Instructor $instructor): void
    {
        $this->instructorService->delete($instructor);
    }
}
