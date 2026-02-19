<?php

declare(strict_types=1);

namespace App\Services\Playback;

use App\Enums\CenterType;
use App\Enums\ExtraViewRequestStatus;
use App\Exceptions\DomainException;
use App\Models\Center;
use App\Models\Course;
use App\Models\ExtraViewRequest;
use App\Models\User;
use App\Models\Video;
use App\Services\Access\CourseAccessService;
use App\Services\Access\EnrollmentAccessService;
use App\Services\Access\StudentAccessService;
use App\Services\AdminNotifications\AdminNotificationDispatcher;
use App\Services\Audit\AuditLogService;
use App\Services\Centers\CenterScopeService;
use App\Services\Settings\Contracts\SettingsResolverServiceInterface;
use App\Support\AuditActions;
use App\Support\ErrorCodes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ExtraViewRequestService
{
    public function __construct(
        private readonly CenterScopeService $centerScopeService,
        private readonly StudentAccessService $studentAccessService,
        private readonly EnrollmentAccessService $enrollmentAccessService,
        private readonly CourseAccessService $courseAccessService,
        private readonly ViewLimitService $viewLimitService,
        private readonly SettingsResolverServiceInterface $settingsResolver,
        private readonly AuditLogService $auditLogService,
        private readonly AdminNotificationDispatcher $notificationDispatcher
    ) {}

    public function create(User $student, Course $course, Video $video, ?string $reason = null): ExtraViewRequest
    {
        $center = $course->center;
        if (! $center instanceof Center) {
            $this->deny(ErrorCodes::NOT_FOUND, 'Course not found.', 404);
        }

        return $this->createForStudent($student, $center, $course, $video, $reason);
    }

    public function createForStudent(
        User $student,
        Center $center,
        Course $course,
        Video $video,
        ?string $reason = null
    ): ExtraViewRequest {
        $this->studentAccessService->assertStudent(
            $student,
            'Only students can request extra views.',
            ErrorCodes::UNAUTHORIZED,
            403
        );
        $this->assertStudentCenterAccess($student, $center);
        $this->courseAccessService->assertCourseInCenter($course, $center);
        $this->assertExtraViewRequestsEnabled($student, $center, $course, $video);

        return DB::transaction(function () use ($student, $course, $video, $reason): ExtraViewRequest {
            $pivot = $this->courseAccessService->getVideoPivotOrFail($course, $video);
            $this->enrollmentAccessService->assertActiveEnrollment($student, $course);

            $pending = ExtraViewRequest::query()
                ->pendingForUserAndVideo($student, $video)
                ->lockForUpdate()
                ->exists();

            if ($pending) {
                $this->deny(ErrorCodes::PENDING_REQUEST_EXISTS, 'A pending request already exists for this video.', 422);
            }

            $remaining = $this->viewLimitService->remaining(
                $student,
                $video,
                $course,
                $pivot->pivot?->view_limit_override
            );

            if ($remaining > 0) {
                $this->deny(ErrorCodes::VIEWS_AVAILABLE, 'Extra views are not allowed while views remain.', 422);
            }

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

            $fresh = $request->fresh() ?? $request;
            $this->notificationDispatcher->dispatchExtraViewRequest($fresh);

            return $fresh;
        });
    }

    public function grantByAdmin(
        User $admin,
        User $student,
        Course $course,
        Video $video,
        int $grantedViews,
        ?string $reason = null,
        ?string $decisionReason = null
    ): ExtraViewRequest {
        if ($grantedViews <= 0) {
            $this->deny(ErrorCodes::INVALID_VIEWS, 'Granted views must be positive.', 422);
        }

        $this->studentAccessService->assertStudent(
            $student,
            'Only students can receive extra views.',
            ErrorCodes::NOT_STUDENT,
            422
        );
        $this->assertAdminCanManageStudent($admin, $student);

        $center = $course->center;
        if (! $center instanceof Center) {
            $this->deny(ErrorCodes::NOT_FOUND, 'Course not found.', 404);
        }

        $this->assertStudentCenterAccess($student, $center);
        $this->courseAccessService->assertCourseInCenter($course, $center);
        $this->courseAccessService->assertVideoInCourse($course, $video);

        $this->enrollmentAccessService->assertActiveEnrollment($student, $course);

        return DB::transaction(function () use ($admin, $student, $course, $video, $grantedViews, $reason, $decisionReason): ExtraViewRequest {
            $pending = ExtraViewRequest::query()
                ->pendingForUserAndVideo($student, $video)
                ->lockForUpdate()
                ->exists();

            if ($pending) {
                $this->deny(
                    ErrorCodes::PENDING_REQUEST_EXISTS,
                    'A pending request already exists for this video. Approve or reject it first.',
                    422
                );
            }

            /** @var ExtraViewRequest $request */
            $request = ExtraViewRequest::create([
                'user_id' => $student->id,
                'video_id' => $video->id,
                'course_id' => $course->id,
                'center_id' => $course->center_id,
                'status' => ExtraViewRequestStatus::Approved,
                'reason' => $reason,
                'granted_views' => $grantedViews,
                'decision_reason' => $decisionReason,
                'decided_by' => $admin->id,
                'decided_at' => Carbon::now(),
            ]);

            $this->audit($admin, AuditActions::EXTRA_VIEW_REQUEST_APPROVED, [
                'request_id' => $request->id,
                'video_id' => $video->id,
                'course_id' => $course->id,
                'center_id' => $course->center_id,
                'student_id' => $student->id,
                'granted_views' => $grantedViews,
                'decision_reason' => $decisionReason,
                'granted_by_admin' => true,
            ], $request->id);

            return $request->fresh() ?? $request;
        });
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

    private function assertAdminCanManageStudent(User $admin, User $student): void
    {
        if ($admin->is_student) {
            $this->deny(ErrorCodes::UNAUTHORIZED, 'Only admins can perform this action.', 403);
        }

        if ($this->isSystemScopedAdmin($admin)) {
            return;
        }

        $this->centerScopeService->assertAdminSameCenter($admin, $student);
    }

    private function assertAdminScope(User $admin, ExtraViewRequest $request): void
    {
        if ($admin->is_student) {
            $this->deny(ErrorCodes::UNAUTHORIZED, 'Only admins can perform this action.', 403);
        }

        if ($this->isSystemScopedAdmin($admin)) {
            return;
        }

        $this->centerScopeService->assertAdminSameCenter($admin, $request);
    }

    private function assertExtraViewRequestsEnabled(User $student, Center $center, Course $course, Video $video): void
    {
        $settings = $this->settingsResolver->resolve($student, $video, $course, $center);
        $enabled = $settings['allow_extra_view_requests'] ?? true;

        if ($enabled === false) {
            $this->deny(ErrorCodes::FORBIDDEN, 'Extra view requests are disabled.', 403);
        }
    }

    private function assertStudentCenterAccess(User $student, Center $center): void
    {
        if ($center->status !== Center::STATUS_ACTIVE) {
            $this->deny(ErrorCodes::CENTER_MISMATCH, 'Center mismatch.', 403);
        }

        if (is_numeric($student->center_id)) {
            if ((int) $student->center_id !== (int) $center->id) {
                $this->deny(ErrorCodes::CENTER_MISMATCH, 'Center mismatch.', 403);
            }

            return;
        }

        if ($center->type !== CenterType::Unbranded) {
            $this->deny(ErrorCodes::CENTER_MISMATCH, 'Center mismatch.', 403);
        }
    }

    private function isSystemScopedAdmin(User $admin): bool
    {
        return ! $admin->is_student && ! is_numeric($admin->center_id);
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
