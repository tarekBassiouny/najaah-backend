<?php

declare(strict_types=1);

namespace App\Actions\Courses;

use App\Models\Section;
use App\Models\User;
use App\Services\Courses\Contracts\CourseStructureServiceInterface;

class ToggleSectionVisibilityAction
{
    public function __construct(
        private readonly CourseStructureServiceInterface $courseStructureService
    ) {}

    public function execute(User $actor, Section $section): Section
    {
        return $this->courseStructureService->toggleSectionVisibility($section, $actor);
    }
}
