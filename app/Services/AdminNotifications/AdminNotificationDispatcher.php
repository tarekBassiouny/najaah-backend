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
use App\Services\AdminNotifications\Contracts\AdminNotificationServiceInterface;

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
                $request->new_device_model ?? 'a new device'
            ),
            data: [
                'entity_type' => 'device_change_request',
                'entity_id' => $request->id,
                'action_url' => '/admin/device-requests/'.$request->id,
                'student_id' => $student->id,
                'student_name' => $student->name,
                'device_model' => $request->new_device_model,
            ],
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
        $centerId = $student->center_id;

        return $this->notificationService->create(
            type: AdminNotificationType::EXTRA_VIEW_REQUEST,
            title: 'New Extra View Request',
            body: sprintf(
                '%s has requested %d extra view(s) for "%s".',
                $student->name,
                $request->requested_views ?? 1,
                $video->title,
            ),
            data: [
                'entity_type' => 'extra_view_request',
                'entity_id' => $request->id,
                'action_url' => '/admin/extra-view-requests/'.$request->id,
                'student_id' => $student->id,
                'student_name' => $student->name,
                'video_id' => $video->id,
                'video_title' => $video->title,
                'requested_views' => $request->requested_views,
            ],
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
                $survey->title
            ),
            data: [
                'entity_type' => 'survey_response',
                'entity_id' => $response->id,
                'action_url' => '/admin/surveys/'.$survey->id.'/responses/'.$response->id,
                'student_id' => $student->id,
                'student_name' => $student->name,
                'survey_id' => $survey->id,
                'survey_title' => $survey->title,
            ],
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
        $centerId = $course->center_id;

        return $this->notificationService->create(
            type: AdminNotificationType::NEW_ENROLLMENT,
            title: 'New Enrollment',
            body: sprintf(
                '%s has enrolled in "%s".',
                $student->name,
                $course->title
            ),
            data: [
                'entity_type' => 'enrollment',
                'entity_id' => $enrollment->id,
                'action_url' => '/admin/enrollments/'.$enrollment->id,
                'student_id' => $student->id,
                'student_name' => $student->name,
                'course_id' => $course->id,
                'course_title' => $course->title,
            ],
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
            data: [
                'entity_type' => 'center',
                'entity_id' => $center->id,
                'action_url' => '/admin/centers/'.$center->id,
                'center_name' => $center->name,
            ],
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
}
