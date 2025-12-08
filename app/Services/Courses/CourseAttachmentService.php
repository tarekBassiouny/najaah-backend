<?php

declare(strict_types=1);

namespace App\Services\Courses;

use App\Models\Course;
use App\Models\Pdf;
use App\Models\Pivots\CoursePdf;
use App\Models\Pivots\CourseVideo;
use App\Models\Video;
use App\Services\Courses\Contracts\CourseAttachmentServiceInterface;
use Illuminate\Validation\ValidationException;

class CourseAttachmentService implements CourseAttachmentServiceInterface
{
    public function assignVideo(Course $course, int $videoId): void
    {
        $video = Video::findOrFail($videoId);
        $this->assertSameCenter($course, $video);

        $existing = CourseVideo::withTrashed()
            ->where('course_id', $course->id)
            ->where('video_id', $video->id)
            ->first();

        if ($existing !== null) {
            if ($existing->trashed()) {
                $existing->section_id = null;
                $existing->visible = true;
                $existing->restore();
                $existing->save();
            }

            return;
        }

        $maxOrder = CourseVideo::where('course_id', $course->id)
            ->whereNull('deleted_at')
            ->max('order_index');
        $order = is_numeric($maxOrder) ? (int) $maxOrder : 0;

        CourseVideo::create([
            'course_id' => $course->id,
            'video_id' => $video->id,
            'section_id' => null,
            'order_index' => $order + 1,
            'visible' => true,
            'view_limit_override' => null,
        ]);
    }

    public function removeVideo(Course $course, int $videoId): void
    {
        CourseVideo::where('course_id', $course->id)
            ->where('video_id', $videoId)
            ->whereNull('deleted_at')
            ->get()
            ->each(function (CourseVideo $pivot): void {
                $pivot->delete();
            });
    }

    public function assignPdf(Course $course, int $pdfId): void
    {
        $pdf = Pdf::findOrFail($pdfId);
        $this->assertSameCenter($course, $pdf);

        $existing = CoursePdf::withTrashed()
            ->where('course_id', $course->id)
            ->where('pdf_id', $pdf->id)
            ->first();

        if ($existing !== null) {
            if ($existing->trashed()) {
                $existing->section_id = null;
                $existing->visible = true;
                $existing->restore();
                $existing->save();
            }

            return;
        }

        $maxOrder = CoursePdf::where('course_id', $course->id)
            ->whereNull('deleted_at')
            ->max('order_index');
        $order = is_numeric($maxOrder) ? (int) $maxOrder : 0;

        CoursePdf::create([
            'course_id' => $course->id,
            'pdf_id' => $pdf->id,
            'section_id' => null,
            'video_id' => null,
            'order_index' => $order + 1,
            'visible' => true,
            'download_permission_override' => null,
        ]);
    }

    public function removePdf(Course $course, int $pdfId): void
    {
        CoursePdf::where('course_id', $course->id)
            ->where('pdf_id', $pdfId)
            ->whereNull('deleted_at')
            ->get()
            ->each(function (CoursePdf $pivot): void {
                $pivot->delete();
            });
    }

    private function assertSameCenter(Course $course, \Illuminate\Database\Eloquent\Model $resource): void
    {
        $resourceCenter = $resource->getAttribute('center_id');
        $creatorCenter = null;

        if (method_exists($resource, 'creator') && $resource->relationLoaded('creator')) {
            /** @var object|null $creator */
            $creator = $resource->getRelation('creator');
            $creatorCenter = $creator?->center_id ?? null;
        }

        $resourceCenterId = $resourceCenter ?? $creatorCenter;

        if ($resourceCenterId !== null && $resourceCenterId !== $course->center_id) {
            throw ValidationException::withMessages([
                'center_id' => ['Attachment must belong to the same center as the course.'],
            ]);
        }
    }
}
