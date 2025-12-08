<?php

declare(strict_types=1);

namespace App\Services\Courses\Contracts;

use App\Models\Course;

interface CourseAttachmentServiceInterface
{
    public function assignVideo(Course $course, int $videoId): void;

    public function removeVideo(Course $course, int $videoId): void;

    public function assignPdf(Course $course, int $pdfId): void;

    public function removePdf(Course $course, int $pdfId): void;
}
