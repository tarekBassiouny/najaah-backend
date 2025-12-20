<?php

declare(strict_types=1);

namespace App\Services\Courses\Contracts;

use App\Models\Course;
use App\Models\Section;
use App\Models\User;

interface CourseStructureServiceInterface
{
    /** @param array<string, mixed> $data */
    public function addSection(Course $course, array $data, User $actor): Section;

    /** @param array<int, int> $orderedIds */
    public function reorderSections(Course $course, array $orderedIds, User $actor): void;

    public function toggleSectionVisibility(Section $section, User $actor): Section;
}
