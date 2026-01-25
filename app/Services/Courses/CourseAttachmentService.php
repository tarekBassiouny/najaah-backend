<?php

declare(strict_types=1);

namespace App\Services\Courses;

use App\Enums\PdfUploadStatus;
use App\Enums\VideoUploadStatus;
use App\Exceptions\AttachmentNotAllowedException;
use App\Exceptions\UploadNotReadyException;
use App\Models\Course;
use App\Models\Pdf;
use App\Models\Pivots\CoursePdf;
use App\Models\Pivots\CourseVideo;
use App\Models\User;
use App\Models\Video;
use App\Services\Centers\CenterScopeService;
use App\Services\Courses\Contracts\CourseAttachmentServiceInterface;
use Illuminate\Support\Facades\Log;

class CourseAttachmentService implements CourseAttachmentServiceInterface
{
    public function __construct(private readonly CenterScopeService $centerScopeService) {}

    public function assignVideo(Course $course, int $videoId, User $actor): void
    {
        $this->centerScopeService->assertAdminSameCenter($actor, $course);
        $video = Video::findOrFail($videoId);
        $this->assertSameCenter($course, $video);
        $this->assertVideoReady($video);

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

    public function removeVideo(Course $course, int $videoId, User $actor): void
    {
        $this->centerScopeService->assertAdminSameCenter($actor, $course);
        CourseVideo::where('course_id', $course->id)
            ->where('video_id', $videoId)
            ->whereNull('deleted_at')
            ->get()
            ->each(function (CourseVideo $pivot): void {
                $pivot->delete();
            });
    }

    public function assignPdf(Course $course, int $pdfId, User $actor): void
    {
        $this->centerScopeService->assertAdminSameCenter($actor, $course);
        $pdf = Pdf::findOrFail($pdfId);
        $this->assertSameCenter($course, $pdf);
        $this->assertPdfReady($pdf);

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
        ]);
    }

    public function removePdf(Course $course, int $pdfId, User $actor): void
    {
        $this->centerScopeService->assertAdminSameCenter($actor, $course);
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
            throw new AttachmentNotAllowedException('Attachment must belong to the same center as the course.', 422);
        }
    }

    private function assertVideoReady(Video $video): void
    {
        if ($video->encoding_status !== VideoUploadStatus::Ready) {
            throw new AttachmentNotAllowedException('Video is not ready to be attached.', 422);
        }

        if ($video->upload_session_id === null) {
            throw new UploadNotReadyException('Video upload session is required.', 422);
        }

        $video->loadMissing('uploadSession');
        $session = $video->uploadSession;

        if ($session === null) {
            throw new UploadNotReadyException('Video upload session is required.', 422);
        }

        if ($session->expires_at !== null && $session->expires_at <= now()) {
            Log::channel('domain')->warning('upload_session_expired', [
                'video_id' => $video->id,
                'session_id' => $session->id,
            ]);
            throw new UploadNotReadyException('Video upload session has expired.', 422);
        }

        if ($session->upload_status !== VideoUploadStatus::Ready) {
            throw new UploadNotReadyException('Video upload session is not ready.', 422);
        }
    }

    private function assertPdfReady(Pdf $pdf): void
    {
        if ($pdf->upload_session_id === null) {
            throw new AttachmentNotAllowedException('PDF is not ready to be attached.', 422);
        }

        $pdf->loadMissing('uploadSession');
        $session = $pdf->uploadSession;

        if ($session === null) {
            throw new UploadNotReadyException('PDF upload session is required.', 422);
        }

        if ($session->expires_at !== null && $session->expires_at <= now()) {
            Log::channel('domain')->warning('upload_session_expired', [
                'pdf_id' => $pdf->id,
                'session_id' => $session->id,
            ]);
            throw new UploadNotReadyException('PDF upload session has expired.', 422);
        }

        if ($session->upload_status !== PdfUploadStatus::Ready) {
            throw new UploadNotReadyException('PDF upload session is not ready.', 422);
        }
    }
}
