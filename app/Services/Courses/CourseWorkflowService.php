<?php

declare(strict_types=1);

namespace App\Services\Courses;

use App\Enums\PdfUploadStatus;
use App\Exceptions\PublishBlockedException;
use App\Models\AuditLog;
use App\Models\Course;
use App\Models\Pivots\CoursePdf;
use App\Models\Pivots\CourseVideo;
use App\Models\Section;
use App\Models\User;
use App\Services\Centers\CenterScopeService;
use App\Services\Courses\Contracts\CourseWorkflowServiceInterface;
use App\Services\Videos\VideoPublishingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CourseWorkflowService implements CourseWorkflowServiceInterface
{
    private VideoPublishingService $videoPublishingService;

    public function __construct(
        private readonly CenterScopeService $centerScopeService,
        ?VideoPublishingService $videoPublishingService = null
    ) {
        $this->videoPublishingService = $videoPublishingService ?? new VideoPublishingService;
    }

    public function publishCourse(Course $course, User $actor): Course
    {
        $this->centerScopeService->assertAdminSameCenter($actor, $course);
        $course->loadMissing(['sections', 'videos']);
        $course->loadMissing(['pdfs']);

        if (method_exists($course, 'trashed') && $course->trashed()) {
            Log::channel('domain')->warning('course_publish_blocked', [
                'course_id' => $course->id,
                'center_id' => $course->center_id,
                'reason' => 'course_deleted',
            ]);
            throw new PublishBlockedException('Course is deleted.', 422);
        }

        if ($course->status === Course::STATUS_PUBLISHED) {
            Log::channel('domain')->warning('course_publish_blocked', [
                'course_id' => $course->id,
                'center_id' => $course->center_id,
                'reason' => 'already_published',
            ]);
            throw new PublishBlockedException('Course is already published.', 422);
        }

        if ($course->sections->isEmpty()) {
            Log::channel('domain')->warning('course_publish_blocked', [
                'course_id' => $course->id,
                'center_id' => $course->center_id,
                'reason' => 'missing_sections',
            ]);
            throw new PublishBlockedException('Course must have at least one section before publishing.', 422);
        }

        $visibleSections = $course->sections->where('visible', true);
        if ($visibleSections->isEmpty()) {
            Log::channel('domain')->warning('course_publish_blocked', [
                'course_id' => $course->id,
                'center_id' => $course->center_id,
                'reason' => 'no_visible_sections',
            ]);
            throw new PublishBlockedException('At least one visible section is required.', 422);
        }

        try {
            foreach ($course->videos as $video) {
                if ((int) $video->center_id !== (int) $course->center_id) {
                    Log::channel('domain')->warning('course_publish_blocked', [
                        'course_id' => $course->id,
                        'center_id' => $course->center_id,
                        'reason' => 'video_center_mismatch',
                    ]);
                    throw new PublishBlockedException('Video must belong to the course center.', 422);
                }

                $this->videoPublishingService->ensurePublishable($video);
            }
        } catch (PublishBlockedException $publishBlockedException) {
            Log::channel('domain')->warning('course_publish_blocked', [
                'course_id' => $course->id,
                'center_id' => $course->center_id,
                'reason' => 'video_not_ready',
            ]);
            throw $publishBlockedException;
        }

        foreach ($course->pdfs as $pdf) {
            $sessionId = $pdf->upload_session_id;

            if ($sessionId === null) {
                Log::channel('domain')->warning('course_publish_blocked', [
                    'course_id' => $course->id,
                    'center_id' => $course->center_id,
                    'reason' => 'pdf_missing_session',
                ]);
                throw new PublishBlockedException('PDF upload session is required.', 422);
            }

            if ((int) $pdf->center_id !== (int) $course->center_id) {
                Log::channel('domain')->warning('course_publish_blocked', [
                    'course_id' => $course->id,
                    'center_id' => $course->center_id,
                    'reason' => 'pdf_center_mismatch',
                ]);
                throw new PublishBlockedException('PDF must belong to the course center.', 422);
            }

            $pdf->loadMissing('uploadSession');
            $session = $pdf->uploadSession;

            if ($session === null || $session->upload_status !== PdfUploadStatus::Ready) {
                Log::channel('domain')->warning('course_publish_blocked', [
                    'course_id' => $course->id,
                    'center_id' => $course->center_id,
                    'reason' => 'pdf_not_ready',
                ]);
                throw new PublishBlockedException('PDF upload session is not ready.', 422);
            }

            if ($session->expires_at !== null && $session->expires_at <= now()) {
                Log::channel('domain')->warning('course_publish_blocked', [
                    'course_id' => $course->id,
                    'center_id' => $course->center_id,
                    'reason' => 'pdf_session_expired',
                ]);
                throw new PublishBlockedException('PDF upload session has expired.', 422);
            }
        }

        $course->status = Course::STATUS_PUBLISHED;
        $course->is_published = true;
        $course->publish_at = now();
        $course->save();

        AuditLog::create([
            'user_id' => null,
            'action' => 'course_published',
            'entity_type' => Course::class,
            'entity_id' => $course->id,
            'metadata' => [
                'course_id' => $course->id,
                'published_at' => $course->publish_at,
            ],
        ]);

        $fresh = $course->fresh(['sections', 'videos']);

        return $fresh ?? $course;
    }

    /** @param array<string, mixed> $options */
    public function cloneCourse(Course $course, User $actor, array $options = []): Course
    {
        $this->centerScopeService->assertAdminSameCenter($actor, $course);

        return DB::transaction(function () use ($course, $options): Course {
            $course->loadMissing(['sections', 'videos', 'pdfs']);

            $clone = $course->replicate();
            $clone->status = Course::STATUS_DRAFT;
            $clone->is_published = false;
            $clone->publish_at = null;
            $clone->cloned_from_id = $course->id;
            $clone->course_code = isset($options['course_code']) && is_string($options['course_code']) ? $options['course_code'] : null;
            $clone->push();

            $sectionMap = [];
            foreach ($course->sections as $section) {
                /** @var Section $section */
                $newSection = $section->replicate();
                $newSection->course_id = $clone->id;
                $newSection->push();
                $sectionMap[$section->id] = $newSection->id;
            }

            $courseVideos = CourseVideo::where('course_id', $course->id)
                ->whereNull('deleted_at')
                ->get();

            foreach ($courseVideos as $pivot) {
                CourseVideo::create([
                    'course_id' => $clone->id,
                    'video_id' => $pivot->video_id,
                    'section_id' => $pivot->section_id !== null && isset($sectionMap[$pivot->section_id]) ? $sectionMap[$pivot->section_id] : null,
                    'order_index' => $pivot->order_index,
                    'visible' => $pivot->visible,
                    'view_limit_override' => $pivot->view_limit_override,
                ]);
            }

            $coursePdfs = CoursePdf::where('course_id', $course->id)
                ->whereNull('deleted_at')
                ->get();

            foreach ($coursePdfs as $pivot) {
                CoursePdf::create([
                    'course_id' => $clone->id,
                    'pdf_id' => $pivot->pdf_id,
                    'section_id' => $pivot->section_id !== null && isset($sectionMap[$pivot->section_id]) ? $sectionMap[$pivot->section_id] : null,
                    'video_id' => $pivot->video_id,
                    'order_index' => $pivot->order_index,
                    'visible' => $pivot->visible,
                ]);
            }

            AuditLog::create([
                'user_id' => null,
                'action' => 'course_cloned',
                'entity_type' => Course::class,
                'entity_id' => $clone->id,
                'metadata' => [
                    'source_course_id' => $course->id,
                    'cloned_course_id' => $clone->id,
                ],
            ]);

            return $clone->fresh(['sections', 'videos', 'pdfs']) ?? $clone;
        });
    }
}
