<?php

declare(strict_types=1);

namespace App\Services\Courses;

use App\Exceptions\AttachmentNotAllowedException;
use App\Models\Course;
use App\Models\Pdf;
use App\Models\Pivots\CoursePdf;
use App\Models\Pivots\CourseVideo;
use App\Models\User;
use App\Models\Video;
use App\Services\Access\PdfAccessService;
use App\Services\Access\VideoAccessService;
use App\Services\Audit\AuditLogService;
use App\Services\Centers\CenterScopeService;
use App\Services\Courses\Contracts\CourseAttachmentServiceInterface;
use App\Support\AuditActions;

class CourseAttachmentService implements CourseAttachmentServiceInterface
{
    public function __construct(
        private readonly CenterScopeService $centerScopeService,
        private readonly VideoAccessService $videoAccessService,
        private readonly PdfAccessService $pdfAccessService,
        private readonly AuditLogService $auditLogService
    ) {}

    public function assignVideo(Course $course, int $videoId, User $actor): void
    {
        $this->centerScopeService->assertAdminSameCenter($actor, $course);
        $video = Video::findOrFail($videoId);
        $this->assertSameCenter($course, $video);
        $this->videoAccessService->assertReadyForAttachment($video);

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

            $this->auditLogService->log($actor, $course, AuditActions::COURSE_VIDEO_ATTACHED, [
                'video_id' => $video->id,
            ]);

            return;
        }

        $maxOrder = CourseVideo::query()
            ->forCourse($course)
            ->notDeleted()
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

        $this->auditLogService->log($actor, $course, AuditActions::COURSE_VIDEO_ATTACHED, [
            'video_id' => $video->id,
        ]);
    }

    public function removeVideo(Course $course, int $videoId, User $actor): void
    {
        $this->centerScopeService->assertAdminSameCenter($actor, $course);
        CourseVideo::query()
            ->forCourse($course)
            ->where('video_id', $videoId)
            ->notDeleted()
            ->get()
            ->each(function (CourseVideo $pivot): void {
                $pivot->delete();
            });

        $this->auditLogService->log($actor, $course, AuditActions::COURSE_VIDEO_REMOVED, [
            'video_id' => $videoId,
        ]);
    }

    public function assignPdf(Course $course, int $pdfId, User $actor): void
    {
        $this->centerScopeService->assertAdminSameCenter($actor, $course);
        $pdf = Pdf::findOrFail($pdfId);
        $this->assertSameCenter($course, $pdf);
        $this->pdfAccessService->assertReadyForAttachment($pdf);

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

            $this->auditLogService->log($actor, $course, AuditActions::COURSE_PDF_ATTACHED, [
                'pdf_id' => $pdf->id,
            ]);

            return;
        }

        $maxOrder = CoursePdf::query()
            ->forCourse($course)
            ->notDeleted()
            ->max('order_index');
        $order = is_numeric($maxOrder) ? (int) $maxOrder : 0;

        CoursePdf::create([
            'course_id' => $course->id,
            'pdf_id' => $pdf->id,
            'section_id' => null,
            'video_id' => null,
            'order_index' => $order + 1,
            'visible' => true,
        ]);

        $this->auditLogService->log($actor, $course, AuditActions::COURSE_PDF_ATTACHED, [
            'pdf_id' => $pdf->id,
        ]);
    }

    public function removePdf(Course $course, int $pdfId, User $actor): void
    {
        $this->centerScopeService->assertAdminSameCenter($actor, $course);
        CoursePdf::query()
            ->forCourse($course)
            ->where('pdf_id', $pdfId)
            ->notDeleted()
            ->get()
            ->each(function (CoursePdf $pivot): void {
                $pivot->delete();
            });

        $this->auditLogService->log($actor, $course, AuditActions::COURSE_PDF_REMOVED, [
            'pdf_id' => $pdfId,
        ]);
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
            throw new AttachmentNotAllowedException('Attachment must belong to the same center as the course.', 422);
        }
    }
}
