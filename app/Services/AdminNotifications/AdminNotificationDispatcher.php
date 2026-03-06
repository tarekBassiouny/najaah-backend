<?php

declare(strict_types=1);

namespace App\Services\AdminNotifications;

use App\Enums\AdminNotificationType;
use App\Models\AdminNotification;
use App\Models\Center;
use App\Models\Course;
use App\Models\DeviceChangeRequest;
use App\Models\Enrollment;
use App\Models\ExtraViewRequest;
use App\Models\Survey;
use App\Models\SurveyResponse;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoAccessRequest;
use App\Services\AdminNotifications\Contracts\AdminNotificationServiceInterface;
use Illuminate\Support\Facades\Log;

class AdminNotificationDispatcher
{
    public function __construct(
        private readonly AdminNotificationServiceInterface $notificationService
    ) {}

    public function dispatchDeviceChangeRequest(DeviceChangeRequest $request): AdminNotification
    {
        /** @var User $student */
        $student = $request->user;
        $centerId = $student->center_id;

        return $this->notificationService->create(
            type: AdminNotificationType::DEVICE_CHANGE_REQUEST,
            title: 'New Device Change Request',
            body: sprintf(
                '%s has requested to change their device to %s.',
                $student->name ?? 'A student',
                $request->new_model !== '' ? $request->new_model : 'a new device'
            ),
            data: array_merge(
                $this->basePayload(
                    entityType: 'device_change_request',
                    entityId: $request->id,
                    centerId: $centerId
                ),
                [
                    'action_url' => $this->centerListPath(
                        $this->ensureCenter($centerId, 'device change request'),
                        '/student-requests/device-change',
                        ['request_id' => (string) $request->id]
                    ),
                    'student_id' => $student->id,
                    'student_name' => $student->name,
                    'device_model' => $request->new_model,
                ]
            ),
            userId: null,
            centerId: $centerId
        );
    }

    public function dispatchExtraViewRequest(ExtraViewRequest $request): AdminNotification
    {
        /** @var User $student */
        $student = $request->user;
        /** @var Video $video */
        $video = $request->video;
        $centerId = is_numeric($request->center_id)
            ? (int) $request->center_id
            : (is_numeric($request->course->center_id) ? (int) $request->course->center_id : null);

        return $this->notificationService->create(
            type: AdminNotificationType::EXTRA_VIEW_REQUEST,
            title: 'New Extra View Request',
            body: sprintf(
                '%s has requested %d extra view(s) for "%s".',
                $student->name,
                1,
                $video->translate('title') ?: 'Untitled Video',
            ),
            data: array_merge(
                $this->basePayload(
                    entityType: 'extra_view_request',
                    entityId: $request->id,
                    centerId: $centerId
                ),
                [
                    'action_url' => $this->centerListPath(
                        $this->ensureCenter($centerId, 'extra view request'),
                        '/student-requests/extra-view',
                        ['request_id' => (string) $request->id]
                    ),
                    'student_id' => $student->id,
                    'student_name' => $student->name,
                    'video_id' => $video->id,
                    'video_title' => $video->translate('title'),
                    'requested_views' => 1,
                ],
            ),
            userId: null,
            centerId: $centerId
        );
    }

    public function dispatchVideoAccessRequest(VideoAccessRequest $request): AdminNotification
    {
        /** @var User $student */
        $student = $request->user;
        /** @var Video $video */
        $video = $request->video;
        $centerId = is_numeric($request->center_id)
            ? (int) $request->center_id
            : (is_numeric($request->course->center_id) ? (int) $request->course->center_id : null);

        return $this->notificationService->create(
            type: AdminNotificationType::VIDEO_ACCESS_REQUEST,
            title: 'New Video Access Request',
            body: sprintf(
                '%s requested access to \"%s\".',
                $student->name,
                $video->translate('title') ?: 'Untitled Video',
            ),
            data: array_merge(
                $this->basePayload(
                    entityType: 'video_access_request',
                    entityId: $request->id,
                    centerId: $centerId
                ),
                [
                    'action_url' => $this->centerListPath(
                        $this->ensureCenter($centerId, 'video access request'),
                        '/student-requests/video-access',
                        ['request_id' => (string) $request->id]
                    ),
                    'student_id' => $student->id,
                    'student_name' => $student->name,
                    'video_id' => $video->id,
                    'video_title' => $video->translate('title'),
                    'course_id' => $request->course_id,
                    'reason' => $request->reason,
                ]
            ),
            userId: null,
            centerId: $centerId
        );
    }

    public function dispatchSurveyResponse(SurveyResponse $response): AdminNotification
    {
        /** @var User $student */
        $student = $response->user;
        /** @var Survey $survey */
        $survey = $response->survey;
        $centerId = $survey->center_id;

        return $this->notificationService->create(
            type: AdminNotificationType::SURVEY_RESPONSE,
            title: 'New Survey Response',
            body: sprintf(
                '%s has submitted a response to "%s".',
                $student->name,
                $survey->translate('title') ?: 'Untitled Survey'
            ),
            data: array_merge(
                $this->basePayload(
                    entityType: 'survey_response',
                    entityId: $response->id,
                    centerId: $centerId
                ),
                [
                    'action_url' => $centerId !== null
                        ? $this->centerDetailPath(
                            $centerId,
                            sprintf('/surveys/%d/responses/%d', $survey->id, $response->id)
                        )
                        : $this->withQuery('/surveys', [
                            'open_results_survey_id' => (string) $survey->id,
                            'focus_tab' => 'responses',
                            'response_id' => (string) $response->id,
                        ]),
                    'student_id' => $student->id,
                    'student_name' => $student->name,
                    'survey_id' => $survey->id,
                    'survey_title' => $survey->translate('title'),
                ]
            ),
            userId: null,
            centerId: $centerId
        );
    }

    public function dispatchNewEnrollment(Enrollment $enrollment): AdminNotification
    {
        /** @var User $student */
        $student = $enrollment->user;
        /** @var Course $course */
        $course = $enrollment->course;
        $courseTitle = $course->translate('title');
        $centerId = $course->center_id;

        return $this->notificationService->create(
            type: AdminNotificationType::NEW_ENROLLMENT,
            title: 'New Enrollment',
            body: sprintf(
                '%s has enrolled in "%s".',
                $student->name,
                $courseTitle ?: 'Untitled Course'
            ),
            data: array_merge(
                $this->basePayload(
                    entityType: 'enrollment',
                    entityId: $enrollment->id,
                    centerId: $centerId
                ),
                [
                    'action_url' => $this->centerListPath(
                        $this->ensureCenter($centerId, 'enrollment'),
                        '/student-requests/enrollments',
                        ['enrollment_id' => (string) $enrollment->id]
                    ),
                    'student_id' => $student->id,
                    'student_name' => $student->name,
                    'course_id' => $course->id,
                    'course_title' => $courseTitle,
                ],
            ),
            userId: null,
            centerId: $centerId
        );
    }

    public function dispatchEnrollmentRequest(Enrollment $enrollment): AdminNotification
    {
        /** @var User $student */
        $student = $enrollment->user;
        /** @var Course $course */
        $course = $enrollment->course;
        $courseTitle = $course->translate('title');
        $centerId = $course->center_id;

        return $this->notificationService->create(
            type: AdminNotificationType::NEW_ENROLLMENT,
            title: 'New Enrollment Request',
            body: sprintf(
                '%s has requested enrollment in "%s".',
                $student->name,
                $courseTitle ?: 'Untitled Course'
            ),
            data: array_merge(
                $this->basePayload(
                    entityType: 'enrollment_request',
                    entityId: $enrollment->id,
                    centerId: $centerId
                ),
                [
                    'action_url' => $this->centerListPath(
                        $this->ensureCenter($centerId, 'enrollment request'),
                        '/student-requests/enrollments',
                        ['enrollment_request_id' => (string) $enrollment->id]
                    ),
                    'student_id' => $student->id,
                    'student_name' => $student->name,
                    'course_id' => $course->id,
                    'course_title' => $courseTitle,
                ],
            ),
            userId: null,
            centerId: $centerId
        );
    }

    public function dispatchCenterOnboarding(Center $center): AdminNotification
    {
        return $this->notificationService->create(
            type: AdminNotificationType::CENTER_ONBOARDING,
            title: 'New Center Onboarded',
            body: sprintf(
                'Center "%s" has been successfully onboarded.',
                $center->name ?? 'A center'
            ),
            data: array_merge(
                $this->basePayload(
                    entityType: 'center',
                    entityId: $center->id,
                    centerId: $center->id
                ),
                [
                    'action_url' => $this->centerDetailPath(
                        $center->id,
                        '/centers/'.$center->id
                    ),
                    'center_name' => $center->name,
                ],
            ),
            userId: null,
            centerId: null
        );
    }

    /**
     * @param  array<string, mixed>|null  $data
     */
    public function dispatchSystemAlert(
        string $title,
        ?string $body = null,
        ?array $data = null,
        ?int $userId = null,
        ?int $centerId = null
    ): AdminNotification {
        return $this->notificationService->create(
            type: AdminNotificationType::SYSTEM_ALERT,
            title: $title,
            body: $body,
            data: $data,
            userId: $userId,
            centerId: $centerId
        );
    }

    /**
     * @return array<string, int|string|bool>
     */
    private function basePayload(string $entityType, int $entityId, ?int $centerId): array
    {
        return [
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'center_id' => $centerId,
            'is_actionable' => $centerId !== null,
            'fallback_url' => $centerId !== null
                ? sprintf('/centers/%d/notifications', $centerId)
                : '/notifications',
        ];
    }

    private function ensureCenter(?int $centerId, string $context): int
    {
        if (is_numeric($centerId)) {
            return $centerId;
        }

        Log::warning('Admin notification missing center context', [
            'context' => $context,
            'center_id' => $centerId,
        ]);

        return 0;
    }

    /**
     * @param  array<string, string>  $query
     */
    private function centerListPath(int $centerId, string $relative, array $query = []): string
    {
        $path = sprintf('/centers/%d%s', $centerId, $relative);

        if (empty($query)) {
            return $path;
        }

        return $this->withQuery($path, $query);
    }

    private function centerDetailPath(int $centerId, string $relative): string
    {
        return sprintf('/centers/%d%s', $centerId, $relative);
    }

    /**
     * @param  array<string, string>  $query
     */
    private function withQuery(string $path, array $query): string
    {
        $params = array_filter($query, fn (?string $value): bool => $value !== null && $value !== '');

        if (empty($params)) {
            return $path;
        }

        $search = http_build_query($params);

        return sprintf('%s?%s', $path, $search);
    }
}
