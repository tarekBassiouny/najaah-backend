<?php

declare(strict_types=1);

namespace App\Services\Courses\Contracts;

use App\Models\Course;
use App\Models\Section;

interface CourseStructureServiceInterface
{
    /** @param array<string, mixed> $data */
    public function addSection(Course $course, array $data): Section;

    /** @param array<int, int> $orderedIds */
    public function reorderSections(Course $course, array $orderedIds): void;

    public function toggleSectionVisibility(Section $section): Section;
}
