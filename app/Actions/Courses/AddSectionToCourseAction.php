<?php

declare(strict_types=1);

namespace App\Actions\Courses;

use App\Actions\Concerns\NormalizesTranslations;
use App\Models\Course;
use App\Models\Section;
use App\Models\User;
use App\Services\Courses\Contracts\CourseStructureServiceInterface;

class AddSectionToCourseAction
{
    use NormalizesTranslations;

    public function __construct(
        private readonly CourseStructureServiceInterface $courseStructureService
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(User $actor, Course $course, array $data): Section
    {
        $data = $this->normalizeTranslations($data, [
            'title_translations',
            'description_translations',
        ]);

        return $this->courseStructureService->addSection($course, $data, $actor);
    }
}
