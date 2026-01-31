<?php

declare(strict_types=1);

namespace App\Services\Access;

use App\Models\Pdf;
use App\Models\Pivots\CoursePdf;
use App\Models\Pivots\CourseVideo;
use App\Models\Section;
use App\Models\Video;

class AttachmentAccessService
{
    public function isVideoAttachedToOtherCourse(Section $section, Video $video): bool
    {
        return CourseVideo::query()
            ->forVideo($video)
            ->where('course_id', '!=', $section->course_id)
            ->notDeleted()
            ->exists();
    }

    public function isPdfAttachedToOtherCourse(Section $section, Pdf $pdf): bool
    {
        return CoursePdf::query()
            ->forPdf($pdf)
            ->where('course_id', '!=', $section->course_id)
            ->notDeleted()
            ->exists();
    }
}
