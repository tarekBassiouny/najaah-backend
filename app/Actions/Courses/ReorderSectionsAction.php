<?php

declare(strict_types=1);

namespace App\Actions\Courses;

use App\Models\Course;
use App\Models\User;
use App\Services\Courses\Contracts\CourseStructureServiceInterface;

class ReorderSectionsAction
{
    public function __construct(
        private readonly CourseStructureServiceInterface $courseStructureService
    ) {}

    /** @param array<int, int> $orderedIds */
    public function execute(User $actor, Course $course, array $orderedIds): void
    {
        $this->courseStructureService->reorderSections($course, $orderedIds, $actor);
    }
}
