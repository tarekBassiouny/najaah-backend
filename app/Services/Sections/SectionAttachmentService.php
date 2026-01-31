<?php

declare(strict_types=1);

namespace App\Services\Sections;

use App\Models\Pdf;
use App\Models\Pivots\CoursePdf;
use App\Models\Pivots\CourseVideo;
use App\Models\Section;
use App\Models\Video;
use App\Services\Access\AttachmentAccessService;
use App\Services\Sections\Contracts\SectionAttachmentServiceInterface;
use Illuminate\Validation\ValidationException;

class SectionAttachmentService implements SectionAttachmentServiceInterface
{
    public function __construct(private readonly AttachmentAccessService $attachmentAccessService) {}

    public function moveVideoToSection(Video $video, Section $section): void
    {
        $this->assertVideoBelongsToCourse($section, $video);

        $pivot = CourseVideo::withTrashed()
            ->where('video_id', $video->id)
            ->where('course_id', $section->course_id)
            ->first();

        if ($pivot !== null && $pivot->course_id !== $section->course_id) {
            throw ValidationException::withMessages([
                'course_id' => ['Video does not belong to this course.'],
            ]);
        }

        if ($pivot === null) {
            CourseVideo::create([
                'course_id' => $section->course_id,
                'video_id' => $video->id,
                'section_id' => $section->id,
                'order_index' => $this->nextVideoOrder($section),
                'visible' => true,
                'view_limit_override' => null,
            ]);

            return;
        }

        $pivot->section_id = $section->id;
        $pivot->order_index = $this->nextVideoOrder($section);
        $pivot->visible = true;

        if ($pivot->trashed()) {
            $pivot->restore();
        }

        $pivot->save();
    }

    public function movePdfToSection(Pdf $pdf, Section $section): void
    {
        $this->assertPdfBelongsToCourse($section, $pdf);

        $pivot = CoursePdf::withTrashed()
            ->where('pdf_id', $pdf->id)
            ->where('course_id', $section->course_id)
            ->first();

        if ($pivot !== null && $pivot->course_id !== $section->course_id) {
            throw ValidationException::withMessages([
                'course_id' => ['PDF does not belong to this course.'],
            ]);
        }

        if ($pivot === null) {
            CoursePdf::create([
                'course_id' => $section->course_id,
                'pdf_id' => $pdf->id,
                'section_id' => $section->id,
                'video_id' => null,
                'order_index' => $this->nextPdfOrder($section),
                'visible' => true,
            ]);

            return;
        }

        $pivot->section_id = $section->id;
        $pivot->video_id = null;
        $pivot->order_index = $this->nextPdfOrder($section);
        $pivot->visible = true;

        if ($pivot->trashed()) {
            $pivot->restore();
        }

        $pivot->save();
    }

    public function isVideoAttached(Video $video, Section $section): bool
    {
        return CourseVideo::query()
            ->forCourseId((int) $section->course_id)
            ->forSectionId((int) $section->id)
            ->forVideo($video)
            ->notDeleted()
            ->exists();
    }

    public function isPdfAttached(Pdf $pdf, Section $section): bool
    {
        return CoursePdf::query()
            ->forCourseId((int) $section->course_id)
            ->forSectionId((int) $section->id)
            ->forPdf($pdf)
            ->notDeleted()
            ->exists();
    }

    private function nextVideoOrder(Section $section): int
    {
        $maxOrder = CourseVideo::query()
            ->forCourseId((int) $section->course_id)
            ->forSectionId((int) $section->id)
            ->notDeleted()
            ->max('order_index');

        return is_numeric($maxOrder) ? (int) $maxOrder + 1 : 1;
    }

    private function nextPdfOrder(Section $section): int
    {
        $maxOrder = CoursePdf::query()
            ->forCourseId((int) $section->course_id)
            ->forSectionId((int) $section->id)
            ->notDeleted()
            ->max('order_index');

        return is_numeric($maxOrder) ? (int) $maxOrder + 1 : 1;
    }

    private function assertVideoBelongsToCourse(Section $section, Video $video): void
    {
        $attachedToOtherCourse = $this->attachmentAccessService->isVideoAttachedToOtherCourse($section, $video);

        if ($attachedToOtherCourse) {
            throw ValidationException::withMessages([
                'course_id' => ['Video is already attached to another course.'],
            ]);
        }
    }

    private function assertPdfBelongsToCourse(Section $section, Pdf $pdf): void
    {
        $attachedToOtherCourse = $this->attachmentAccessService->isPdfAttachedToOtherCourse($section, $pdf);

        if ($attachedToOtherCourse) {
            throw ValidationException::withMessages([
                'course_id' => ['PDF is already attached to another course.'],
            ]);
        }
    }
}
