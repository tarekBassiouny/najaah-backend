<?php

declare(strict_types=1);

namespace App\Actions\Instructors;

use App\Models\Instructor;
use App\Services\InstructorService;

class CreateInstructorAction
{
    public function __construct(
        private readonly InstructorService $instructorService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data): Instructor
    {
        return $this->instructorService->create($data);
    }
}
