<?php

declare(strict_types=1);

namespace App\Services\Playback;

use App\Enums\ExtraViewRequestStatus;
use App\Exceptions\DomainException;
use App\Models\Course;
use App\Models\ExtraViewRequest;
use App\Models\User;
use App\Models\Video;
use App\Services\Access\CourseAccessService;
use App\Services\Access\EnrollmentAccessService;
use App\Services\Access\StudentAccessService;
use App\Services\Audit\AuditLogService;
use App\Services\Centers\CenterScopeService;
use App\Support\AuditActions;
use App\Support\ErrorCodes;
use Illuminate\Support\Carbon;

class ExtraViewRequestService
{
    public function __construct(
        private readonly CenterScopeService $centerScopeService,
        private readonly StudentAccessService $studentAccessService,
        private readonly EnrollmentAccessService $enrollmentAccessService,
        private readonly CourseAccessService $courseAccessService,
        private readonly AuditLogService $auditLogService
    ) {}

    public function create(User $student, Course $course, Video $video, ?string $reason = null): ExtraViewRequest
    {
        $this->studentAccessService->assertStudent(
            $student,
            'Only students can request extra views.',
            ErrorCodes::UNAUTHORIZED,
            403
        );
        $this->enrollmentAccessService->assertActiveEnrollment($student, $course);
        $this->courseAccessService->assertVideoInCourse($course, $video);
        $this->assertNoPendingRequest($student, $video);

        /** @var ExtraViewRequest $request */
        $request = ExtraViewRequest::create([
            'user_id' => $student->id,
            'video_id' => $video->id,
            'course_id' => $course->id,
            'center_id' => $course->center_id,
            'status' => ExtraViewRequestStatus::Pending,
            'reason' => $reason,
        ]);

        $this->audit($student, AuditActions::EXTRA_VIEW_REQUEST_CREATED, [
            'video_id' => $video->id,
            'course_id' => $course->id,
            'center_id' => $course->center_id,
        ], $request->id);

        return $request->fresh() ?? $request;
    }

    public function approve(User $admin, ExtraViewRequest $request, int $grantedViews, ?string $decisionReason = null): ExtraViewRequest
    {
        $this->assertAdminScope($admin, $request);

        if ($request->status !== ExtraViewRequestStatus::Pending) {
            $this->deny(ErrorCodes::INVALID_STATE, 'Only pending requests can be approved.', 409);
        }

        if ($grantedViews <= 0) {
            $this->deny(ErrorCodes::INVALID_VIEWS, 'Granted views must be positive.', 422);
        }

        $request->status = ExtraViewRequestStatus::Approved;
        $request->granted_views = $grantedViews;
        $request->decision_reason = $decisionReason;
        $request->decided_by = $admin->id;
        $request->decided_at = Carbon::now();
        $request->save();

        $this->audit($admin, AuditActions::EXTRA_VIEW_REQUEST_APPROVED, [
            'request_id' => $request->id,
            'video_id' => $request->video_id,
            'course_id' => $request->course_id,
            'center_id' => $request->center_id,
            'granted_views' => $grantedViews,
            'decision_reason' => $decisionReason,
        ], $request->id);

        return $request->fresh() ?? $request;
    }

    public function reject(User $admin, ExtraViewRequest $request, ?string $decisionReason = null): ExtraViewRequest
    {
        $this->assertAdminScope($admin, $request);

        if ($request->status !== ExtraViewRequestStatus::Pending) {
            $this->deny(ErrorCodes::INVALID_STATE, 'Only pending requests can be rejected.', 409);
        }

        $request->status = ExtraViewRequestStatus::Rejected;
        $request->decision_reason = $decisionReason;
        $request->decided_by = $admin->id;
        $request->decided_at = Carbon::now();
        $request->save();

        $this->audit($admin, AuditActions::EXTRA_VIEW_REQUEST_REJECTED, [
            'request_id' => $request->id,
            'video_id' => $request->video_id,
            'course_id' => $request->course_id,
            'center_id' => $request->center_id,
            'decision_reason' => $decisionReason,
        ], $request->id);

        return $request->fresh() ?? $request;
    }

    private function assertNoPendingRequest(User $student, Video $video): void
    {
        $pending = ExtraViewRequest::query()
            ->pendingForUserAndVideo($student, $video)
            ->exists();

        if ($pending) {
            $this->deny(ErrorCodes::PENDING_REQUEST_EXISTS, 'A pending request already exists for this video.', 422);
        }
    }

    private function assertAdminScope(User $admin, ExtraViewRequest $request): void
    {
        if ($admin->is_student) {
            $this->deny(ErrorCodes::UNAUTHORIZED, 'Only admins can perform this action.', 403);
        }

        $this->centerScopeService->assertAdminSameCenter($admin, $request);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function audit(User $actor, string $action, array $metadata, int $entityId): void
    {
        $this->auditLogService->logByType($actor, ExtraViewRequest::class, $entityId, $action, $metadata);
    }

    /**
     * @return never
     */
    private function deny(string $code, string $message, int $status): void
    {
        throw new DomainException($message, $code, $status);
    }
}
