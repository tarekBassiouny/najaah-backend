<?php

declare(strict_types=1);

namespace App\Services\Courses;

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
use Illuminate\Validation\ValidationException;

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

        if ($course->status === 3) {
            throw ValidationException::withMessages([
                'status' => ['Course is already published.'],
            ]);
        }

        if ($course->sections->isEmpty()) {
            throw ValidationException::withMessages([
                'sections' => ['Course must have at least one section before publishing.'],
            ]);
        }

        foreach ($course->videos as $video) {
            $this->videoPublishingService->ensurePublishable($video);
        }

        $course->status = 3;
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
            $clone->status = 0;
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
                    'download_permission_override' => $pivot->download_permission_override,
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
