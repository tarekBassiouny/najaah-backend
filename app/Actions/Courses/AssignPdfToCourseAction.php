<?php

declare(strict_types=1);

namespace App\Actions\Courses;

use App\Models\Course;
use App\Models\User;
use App\Services\Courses\Contracts\CourseAttachmentServiceInterface;

class AssignPdfToCourseAction
{
    public function __construct(
        private readonly CourseAttachmentServiceInterface $courseAttachmentService
    ) {}

    public function execute(User $actor, Course $course, int $pdfId): void
    {
        $this->courseAttachmentService->assignPdf($course, $pdfId, $actor);
    }
}
