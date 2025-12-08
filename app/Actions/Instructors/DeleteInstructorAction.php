<?php

declare(strict_types=1);

namespace App\Actions\Instructors;

use App\Models\Instructor;
use App\Services\Instructors\Contracts\InstructorServiceInterface;

class DeleteInstructorAction
{
    public function __construct(private readonly InstructorServiceInterface $instructorService) {}

    public function execute(Instructor $instructor): void
    {
        $this->instructorService->delete($instructor);
    }
}
