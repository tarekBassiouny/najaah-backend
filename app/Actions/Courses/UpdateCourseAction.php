<?php

declare(strict_types=1);

namespace App\Actions\Courses;

use App\Actions\Concerns\NormalizesTranslations;
use App\Models\Course;
use App\Services\Courses\Contracts\CourseServiceInterface;

class UpdateCourseAction
{
    use NormalizesTranslations;

    public function __construct(
        private readonly CourseServiceInterface $courseService
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(Course $course, array $data): Course
    {
        $data = $this->normalizeTranslations($data, [
            'title_translations',
            'description_translations',
            'college_translations',
        ], [
            'title_translations' => $course->title_translations ?? [],
            'description_translations' => $course->description_translations ?? [],
            'college_translations' => $course->college_translations ?? [],
        ]);

        return $this->courseService->update($course, $data);
    }
}
