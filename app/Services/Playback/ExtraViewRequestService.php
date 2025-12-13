<?php

declare(strict_types=1);

namespace App\Services\Playback;

use App\Models\AuditLog;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\ExtraViewRequest;
use App\Models\User;
use App\Models\Video;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Carbon;

class ExtraViewRequestService
{
    public function create(User $student, Course $course, Video $video, ?string $reason = null): ExtraViewRequest
    {
        $this->assertStudent($student);
        $this->assertEnrollment($student, $course);
        $this->assertVideoInCourse($course, $video);
        $this->assertNoPendingRequest($student, $video);

        /** @var ExtraViewRequest $request */
        $request = ExtraViewRequest::create([
            'user_id' => $student->id,
            'video_id' => $video->id,
            'course_id' => $course->id,
            'center_id' => $course->center_id,
            'status' => ExtraViewRequest::STATUS_PENDING,
            'reason' => $reason,
        ]);

        $this->audit($student, 'extra_view_request_created', [
            'video_id' => $video->id,
            'course_id' => $course->id,
            'center_id' => $course->center_id,
        ], $request->id);

        return $request->fresh() ?? $request;
    }

    public function approve(User $admin, ExtraViewRequest $request, int $grantedViews, ?string $decisionReason = null): ExtraViewRequest
    {
        $this->assertAdminScope($admin, $request);

        if ($request->status !== ExtraViewRequest::STATUS_PENDING) {
            $this->deny('INVALID_STATE', 'Only pending requests can be approved.', 409);
        }

        if ($grantedViews <= 0) {
            $this->deny('INVALID_VIEWS', 'Granted views must be positive.', 422);
        }

        $request->status = ExtraViewRequest::STATUS_APPROVED;
        $request->granted_views = $grantedViews;
        $request->decision_reason = $decisionReason;
        $request->decided_by = $admin->id;
        $request->decided_at = Carbon::now();
        $request->save();

        $this->audit($admin, 'extra_view_request_approved', [
            'request_id' => $request->id,
            'video_id' => $request->video_id,
            'granted_views' => $grantedViews,
            'decision_reason' => $decisionReason,
        ], $request->id);

        return $request->fresh() ?? $request;
    }

    public function reject(User $admin, ExtraViewRequest $request, ?string $decisionReason = null): ExtraViewRequest
    {
        $this->assertAdminScope($admin, $request);

        if ($request->status !== ExtraViewRequest::STATUS_PENDING) {
            $this->deny('INVALID_STATE', 'Only pending requests can be rejected.', 409);
        }

        $request->status = ExtraViewRequest::STATUS_REJECTED;
        $request->decision_reason = $decisionReason;
        $request->decided_by = $admin->id;
        $request->decided_at = Carbon::now();
        $request->save();

        $this->audit($admin, 'extra_view_request_rejected', [
            'request_id' => $request->id,
            'video_id' => $request->video_id,
            'decision_reason' => $decisionReason,
        ], $request->id);

        return $request->fresh() ?? $request;
    }

    private function assertStudent(User $user): void
    {
        if (! $user->is_student) {
            $this->deny('UNAUTHORIZED', 'Only students can request extra views.', 403);
        }
    }

    private function assertEnrollment(User $student, Course $course): void
    {
        $enrollment = Enrollment::where('user_id', $student->id)
            ->where('course_id', $course->id)
            ->where('status', Enrollment::STATUS_ACTIVE)
            ->first();

        if ($enrollment === null) {
            $this->deny('ENROLLMENT_REQUIRED', 'Active enrollment required.', 403);
        }
    }

    private function assertVideoInCourse(Course $course, Video $video): void
    {
        $exists = $course->videos()
            ->where('videos.id', $video->id)
            ->wherePivotNull('deleted_at')
            ->exists();

        if (! $exists) {
            $this->deny('VIDEO_NOT_IN_COURSE', 'Video not available for this course.', 404);
        }
    }

    private function assertNoPendingRequest(User $student, Video $video): void
    {
        $pending = ExtraViewRequest::where('user_id', $student->id)
            ->where('video_id', $video->id)
            ->where('status', ExtraViewRequest::STATUS_PENDING)
            ->whereNull('deleted_at')
            ->exists();

        if ($pending) {
            $this->deny('PENDING_REQUEST_EXISTS', 'A pending request already exists for this video.', 422);
        }
    }

    private function assertAdminScope(User $admin, ExtraViewRequest $request): void
    {
        if ($admin->is_student) {
            $this->deny('UNAUTHORIZED', 'Only admins can perform this action.', 403);
        }

        if ($admin->center_id !== null && $admin->center_id !== $request->center_id) {
            $this->deny('FORBIDDEN', 'You are not allowed to manage this request.', 403);
        }
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function audit(User $actor, string $action, array $metadata, int $entityId): void
    {
        AuditLog::create([
            'user_id' => $actor->id,
            'action' => $action,
            'entity_type' => ExtraViewRequest::class,
            'entity_id' => $entityId,
            'metadata' => $metadata,
        ]);
    }

    /**
     * @return never
     */
    private function deny(string $code, string $message, int $status): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ], $status));
    }
}
