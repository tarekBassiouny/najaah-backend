<?php

declare(strict_types=1);

namespace App\Services\VideoAccess;

use App\Enums\CenterType;
use App\Enums\VideoAccessCodeStatus;
use App\Enums\VideoAccessRequestStatus;
use App\Enums\WhatsAppCodeFormat;
use App\Exceptions\DomainException;
use App\Models\Center;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoAccessCode;
use App\Models\VideoAccessRequest;
use App\Services\Access\CourseAccessService;
use App\Services\Access\EnrollmentAccessService;
use App\Services\Access\StudentAccessService;
use App\Services\AdminNotifications\AdminNotificationDispatcher;
use App\Services\Audit\AuditLogService;
use App\Services\Centers\CenterScopeService;
use App\Services\VideoAccess\Contracts\VideoApprovalCodeServiceInterface;
use App\Services\VideoAccess\Contracts\VideoApprovalRequestServiceInterface;
use App\Services\VideoAccess\Contracts\VideoApprovalServiceInterface;
use App\Services\VideoAccess\Data\ApprovalResult;
use App\Support\AuditActions;
use App\Support\ErrorCodes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class VideoApprovalRequestService implements VideoApprovalRequestServiceInterface
{
    public function __construct(
        private readonly CenterScopeService $centerScopeService,
        private readonly StudentAccessService $studentAccessService,
        private readonly EnrollmentAccessService $enrollmentAccessService,
        private readonly CourseAccessService $courseAccessService,
        private readonly VideoApprovalServiceInterface $videoApprovalService,
        private readonly VideoApprovalCodeServiceInterface $videoApprovalCodeService,
        private readonly AuditLogService $auditLogService,
        private readonly AdminNotificationDispatcher $notificationDispatcher
    ) {}

    public function createForStudent(
        User $student,
        Center $center,
        Course $course,
        Video $video,
        ?string $reason = null
    ): VideoAccessRequest {
        $this->studentAccessService->assertStudent(
            $student,
            'Only students can request video access.',
            ErrorCodes::UNAUTHORIZED,
            403
        );
        $this->assertStudentCenterAccess($student, $center);
        $this->courseAccessService->assertCourseInCenter($course, $center);
        $this->courseAccessService->assertVideoInCourse($course, $video);

        if (! $this->videoApprovalService->requiresApproval($center, $course)) {
            $this->deny(ErrorCodes::FORBIDDEN, 'Video approval is disabled for this course.', 403);
        }

        if ($this->videoApprovalService->hasAccess($student, $video, $course)) {
            $this->deny(ErrorCodes::VIDEO_ACCESS_ALREADY_GRANTED, 'Video access already granted.', 422);
        }

        return DB::transaction(function () use ($student, $course, $video, $reason): VideoAccessRequest {
            $enrollment = $this->enrollmentAccessService->assertActiveEnrollment($student, $course);

            $pending = VideoAccessRequest::query()
                ->where('user_id', $student->id)
                ->where('video_id', $video->id)
                ->where('course_id', $course->id)
                ->pending()
                ->lockForUpdate()
                ->exists();

            if ($pending) {
                $this->deny(ErrorCodes::VIDEO_ACCESS_REQUEST_EXISTS, 'A pending video access request already exists.', 422);
            }

            /** @var VideoAccessRequest $request */
            $request = VideoAccessRequest::query()->create([
                'user_id' => $student->id,
                'video_id' => $video->id,
                'course_id' => $course->id,
                'center_id' => $course->center_id,
                'enrollment_id' => $enrollment->id,
                'status' => VideoAccessRequestStatus::Pending,
                'reason' => $reason,
            ]);

            $this->auditLogService->logByType(
                $student,
                VideoAccessRequest::class,
                (int) $request->id,
                AuditActions::VIDEO_ACCESS_REQUEST_CREATED,
                [
                    'video_id' => $video->id,
                    'course_id' => $course->id,
                    'center_id' => $course->center_id,
                ]
            );

            $fresh = $request->fresh(['user', 'video', 'course']) ?? $request;
            $this->notificationDispatcher->dispatchVideoAccessRequest($fresh);

            return $fresh;
        });
    }

    public function approve(
        User $admin,
        VideoAccessRequest $request,
        ?string $decisionReason = null,
        bool $sendWhatsApp = false,
        ?WhatsAppCodeFormat $format = null
    ): ApprovalResult {
        $this->assertAdminScope($admin, $request);

        if ($request->status !== VideoAccessRequestStatus::Pending) {
            $this->deny(ErrorCodes::INVALID_STATE, 'Only pending requests can be approved.', 409);
        }

        return DB::transaction(function () use ($admin, $request, $decisionReason, $sendWhatsApp, $format): ApprovalResult {
            /** @var VideoAccessRequest|null $lockedRequest */
            $lockedRequest = VideoAccessRequest::query()
                ->whereKey($request->id)
                ->lockForUpdate()
                ->first();

            if (! $lockedRequest instanceof VideoAccessRequest) {
                $this->deny(ErrorCodes::NOT_FOUND, 'Video access request not found.', 404);
            }

            if ($lockedRequest->status !== VideoAccessRequestStatus::Pending) {
                $this->deny(ErrorCodes::INVALID_STATE, 'Only pending requests can be approved.', 409);
            }

            $lockedRequest->status = VideoAccessRequestStatus::Approved;
            $lockedRequest->decision_reason = $decisionReason;
            $lockedRequest->decided_by = $admin->id;
            $lockedRequest->decided_at = Carbon::now();
            $lockedRequest->save();

            /** @var User $student */
            $student = $lockedRequest->user;
            /** @var Video $video */
            $video = $lockedRequest->video;
            /** @var Course $course */
            $course = $lockedRequest->course;
            /** @var Enrollment $enrollment */
            $enrollment = $lockedRequest->enrollment;

            $generatedCode = $this->videoApprovalCodeService->generate(
                admin: $admin,
                student: $student,
                video: $video,
                course: $course,
                enrollment: $enrollment,
                request: $lockedRequest
            );

            $sent = false;
            $sendError = null;
            if ($sendWhatsApp) {
                try {
                    $this->videoApprovalCodeService->sendViaWhatsApp(
                        $generatedCode,
                        $format ?? WhatsAppCodeFormat::TextCode
                    );
                    $sent = true;
                } catch (\Throwable $throwable) {
                    $sendError = $throwable->getMessage();
                }
            }

            $this->auditLogService->logByType(
                $admin,
                VideoAccessRequest::class,
                (int) $lockedRequest->id,
                AuditActions::VIDEO_ACCESS_REQUEST_APPROVED,
                [
                    'video_id' => $lockedRequest->video_id,
                    'course_id' => $lockedRequest->course_id,
                    'center_id' => $lockedRequest->center_id,
                    'decision_reason' => $decisionReason,
                    'generated_code_id' => $generatedCode->id,
                    'whatsapp_sent' => $sent,
                    'whatsapp_error' => $sendError,
                ]
            );

            return new ApprovalResult(
                request: $lockedRequest->fresh(['user', 'video', 'course', 'center', 'decider']) ?? $lockedRequest,
                generatedCode: $generatedCode,
                whatsAppSent: $sent,
                whatsAppError: $sendError,
            );
        });
    }

    public function reject(User $admin, VideoAccessRequest $request, ?string $decisionReason = null): VideoAccessRequest
    {
        $this->assertAdminScope($admin, $request);

        if ($request->status !== VideoAccessRequestStatus::Pending) {
            $this->deny(ErrorCodes::INVALID_STATE, 'Only pending requests can be rejected.', 409);
        }

        $request->status = VideoAccessRequestStatus::Rejected;
        $request->decision_reason = $decisionReason;
        $request->decided_by = $admin->id;
        $request->decided_at = Carbon::now();
        $request->save();

        $this->auditLogService->logByType(
            $admin,
            VideoAccessRequest::class,
            (int) $request->id,
            AuditActions::VIDEO_ACCESS_REQUEST_REJECTED,
            [
                'video_id' => $request->video_id,
                'course_id' => $request->course_id,
                'center_id' => $request->center_id,
                'decision_reason' => $decisionReason,
            ]
        );

        return $request->fresh(['user', 'video', 'course', 'center', 'decider']) ?? $request;
    }

    public function bulkApprove(
        User $admin,
        array $requestIds,
        ?string $decisionReason = null,
        bool $sendWhatsApp = false,
        ?WhatsAppCodeFormat $format = null,
        ?int $forcedCenterId = null
    ): array {
        $uniqueIds = array_values(array_unique(array_map('intval', $requestIds)));
        $query = VideoAccessRequest::query()->whereIn('id', $uniqueIds);

        if ($forcedCenterId !== null) {
            $query->where('center_id', $forcedCenterId);
        }

        $requests = $query->get()->keyBy('id');

        $results = [];
        $approvedCount = 0;
        $codesGenerated = 0;
        $whatsAppSent = 0;
        $whatsAppFailed = 0;
        $skipped = [];
        $failed = [];

        foreach ($uniqueIds as $requestId) {
            /** @var VideoAccessRequest|null $request */
            $request = $requests->get($requestId);

            if (! $request instanceof VideoAccessRequest) {
                $failed[] = [
                    'request_id' => $requestId,
                    'reason' => 'Video access request not found.',
                ];

                continue;
            }

            if ($request->status !== VideoAccessRequestStatus::Pending) {
                $skipped[] = $requestId;

                continue;
            }

            try {
                $approved = $this->approve($admin, $request, $decisionReason, $sendWhatsApp, $format);
                $approvedCount++;
                $codesGenerated++;
                if ($approved->whatsAppSent) {
                    $whatsAppSent++;
                }

                if ($approved->whatsAppError !== null) {
                    $whatsAppFailed++;
                }

                $results[] = [
                    'request_id' => $requestId,
                    'code' => $approved->generatedCode->code,
                    'whatsapp_sent' => $approved->whatsAppSent,
                    'error' => $approved->whatsAppError,
                ];
            } catch (\Throwable $throwable) {
                $failed[] = [
                    'request_id' => $requestId,
                    'reason' => $throwable->getMessage(),
                ];
            }
        }

        return [
            'approved' => $approvedCount,
            'codes_generated' => $codesGenerated,
            'whatsapp_sent' => $whatsAppSent,
            'whatsapp_failed' => $whatsAppFailed,
            'results' => $results,
            'skipped' => $skipped,
            'failed' => $failed,
        ];
    }

    public function bulkReject(
        User $admin,
        array $requestIds,
        ?string $decisionReason = null,
        ?int $forcedCenterId = null
    ): array {
        $uniqueIds = array_values(array_unique(array_map('intval', $requestIds)));
        $query = VideoAccessRequest::query()->whereIn('id', $uniqueIds);

        if ($forcedCenterId !== null) {
            $query->where('center_id', $forcedCenterId);
        }

        $requests = $query->get()->keyBy('id');

        $rejected = 0;
        $skipped = [];
        $failed = [];

        foreach ($uniqueIds as $requestId) {
            /** @var VideoAccessRequest|null $request */
            $request = $requests->get($requestId);

            if (! $request instanceof VideoAccessRequest) {
                $failed[] = [
                    'request_id' => $requestId,
                    'reason' => 'Video access request not found.',
                ];

                continue;
            }

            if ($request->status !== VideoAccessRequestStatus::Pending) {
                $skipped[] = $requestId;

                continue;
            }

            try {
                $this->reject($admin, $request, $decisionReason);
                $rejected++;
            } catch (\Throwable $throwable) {
                $failed[] = [
                    'request_id' => $requestId,
                    'reason' => $throwable->getMessage(),
                ];
            }
        }

        return [
            'rejected' => $rejected,
            'skipped' => $skipped,
            'failed' => $failed,
        ];
    }

    public function statusForStudent(User $student, Course $course, Video $video): array
    {
        $center = $course->center;

        if (! $center instanceof Center) {
            $this->deny(ErrorCodes::NOT_FOUND, 'Course not found.', 404);
        }

        $this->enrollmentAccessService->assertActiveEnrollment($student, $course);

        $requiresApproval = $this->videoApprovalService->requiresApproval($center, $course);

        if (! $requiresApproval) {
            return [
                'has_access' => true,
                'status' => null,
                'pending_request_id' => null,
                'can_request' => false,
            ];
        }

        $hasAccess = $this->videoApprovalService->hasAccess($student, $video, $course);

        if ($hasAccess) {
            return [
                'has_access' => true,
                'status' => 'granted',
                'pending_request_id' => null,
                'can_request' => false,
            ];
        }

        /** @var VideoAccessRequest|null $pending */
        $pending = VideoAccessRequest::query()
            ->where('user_id', $student->id)
            ->where('video_id', $video->id)
            ->where('course_id', $course->id)
            ->pending()
            ->orderByDesc('id')
            ->first();

        if ($pending instanceof VideoAccessRequest) {
            return [
                'has_access' => false,
                'status' => 'pending',
                'pending_request_id' => $pending->id,
                'can_request' => false,
            ];
        }

        /** @var VideoAccessCode|null $activeCode */
        $activeCode = VideoAccessCode::query()
            ->where('user_id', $student->id)
            ->where('video_id', $video->id)
            ->where('course_id', $course->id)
            ->where('status', VideoAccessCodeStatus::Active->value)
            ->orderByDesc('id')
            ->first();

        if ($activeCode instanceof VideoAccessCode) {
            if ($activeCode->expires_at !== null && $activeCode->expires_at->isPast()) {
                $activeCode->status = VideoAccessCodeStatus::Expired;
                $activeCode->save();
            } else {
                return [
                    'has_access' => false,
                    'status' => 'approved',
                    'pending_request_id' => null,
                    'can_request' => false,
                ];
            }
        }

        $rejected = VideoAccessRequest::query()
            ->where('user_id', $student->id)
            ->where('video_id', $video->id)
            ->where('course_id', $course->id)
            ->rejected()
            ->exists();

        if ($rejected) {
            return [
                'has_access' => false,
                'status' => 'rejected',
                'pending_request_id' => null,
                'can_request' => true,
            ];
        }

        return [
            'has_access' => false,
            'status' => 'locked',
            'pending_request_id' => null,
            'can_request' => true,
        ];
    }

    private function assertAdminScope(User $admin, VideoAccessRequest $request): void
    {
        if ($admin->is_student) {
            $this->deny(ErrorCodes::UNAUTHORIZED, 'Only admins can perform this action.', 403);
        }

        if ($this->isSystemScopedAdmin($admin)) {
            return;
        }

        $this->centerScopeService->assertAdminCenterId($admin, (int) $request->center_id);
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
     * @return never
     */
    private function deny(string $code, string $message, int $status): void
    {
        throw new DomainException($message, $code, $status);
    }
}
