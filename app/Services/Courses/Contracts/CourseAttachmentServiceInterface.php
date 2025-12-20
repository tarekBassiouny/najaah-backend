<?php

declare(strict_types=1);

namespace App\Services\Courses\Contracts;

use App\Models\Course;
use App\Models\User;

interface CourseAttachmentServiceInterface
{
    public function assignVideo(Course $course, int $videoId, User $actor): void;

    public function removeVideo(Course $course, int $videoId, User $actor): void;

    public function assignPdf(Course $course, int $pdfId, User $actor): void;

    public function removePdf(Course $course, int $pdfId, User $actor): void;
}
