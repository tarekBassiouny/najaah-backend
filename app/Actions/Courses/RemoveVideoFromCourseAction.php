<?php

declare(strict_types=1);

namespace App\Actions\Courses;

use App\Models\Course;
use App\Services\Courses\Contracts\CourseAttachmentServiceInterface;

class RemoveVideoFromCourseAction
{
    public function __construct(
        private readonly CourseAttachmentServiceInterface $courseAttachmentService
    ) {}

    public function execute(Course $course, int $videoId): void
    {
        $this->courseAttachmentService->removeVideo($course, $videoId);
    }
}
