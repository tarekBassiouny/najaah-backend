<?php

declare(strict_types=1);

namespace App\Services\Playback;

use App\Models\CenterSetting;
use App\Models\Course;
use App\Models\CourseSetting;
use App\Models\Enrollment;
use App\Models\Section;
use App\Models\StudentSetting;
use App\Models\User;
use App\Models\UserDevice;
use App\Models\Video;
use App\Models\VideoSetting;
use App\Services\Enrollments\Contracts\EnrollmentServiceInterface;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Carbon;

class PlaybackAuthorizationService
{
    public function __construct(
        private readonly EnrollmentServiceInterface $enrollmentService,
        private readonly PlaybackSessionService $sessionService
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function authorize(User $user, Course $course, Video $video, ?Section $section, string $deviceId): array
    {
        $this->assertStudent($user);
        $enrollment = $this->enrollmentService->getActiveEnrollment($user, $course);
        if (! $enrollment instanceof Enrollment) {
            $this->deny('ENROLLMENT_REQUIRED', 'Active enrollment is required for playback.', 403);
        }

        $this->assertCourseAccessible($course);
        $pivot = $this->assertVideoAttached($course, $video);
        $this->assertSectionVisible($course, $section);
        $device = $this->assertDeviceApproved($user, $deviceId);
        $this->assertConcurrencyFree($user);
        $this->assertWithinViewLimit($user, $video, $course, $pivot->view_limit_override);

        $signedUrl = $this->signPlaybackUrl($video);
        $session = $this->sessionService->startSession($user, $video, $device);

        return [
            'playback_url' => $signedUrl,
            'session_id' => $session->id,
            'expires_at' => Carbon::now()->addSeconds(120),
        ];
    }

    private function assertStudent(User $user): void
    {
        if (! $user->is_student) {
            $this->deny('UNAUTHORIZED', 'Only students can play videos.', 403);
        }
    }

    private function assertCourseAccessible(Course $course): void
    {
        if ((int) $course->status !== 3 || (bool) $course->trashed()) {
            $this->deny('COURSE_UNAVAILABLE', 'Course is not published.', 404);
        }
    }

    private function assertSectionVisible(Course $course, ?Section $section): void
    {
        if ($section === null) {
            return;
        }

        if ((int) $section->course_id !== (int) $course->id || $section->trashed() || $section->visible === false) {
            $this->deny('SECTION_UNAVAILABLE', 'Section is not accessible.', 404);
        }
    }

    private function assertVideoAttached(Course $course, Video $video): \Illuminate\Database\Eloquent\Relations\Pivot
    {
        $attached = $course->videos()
            ->where('videos.id', $video->id)
            ->wherePivotNull('deleted_at')
            ->first();

        if ($attached === null) {
            $this->deny('VIDEO_NOT_IN_COURSE', 'Video not available in this course.', 404);
        }

        /** @var \Illuminate\Database\Eloquent\Relations\Pivot $pivot */
        $pivot = $attached->pivot;

        $visible = $pivot->getAttribute('visible');
        if ($visible === false) {
            $this->deny('VIDEO_UNAVAILABLE', 'Video is hidden.', 404);
        }

        return $pivot;
    }

    private function assertDeviceApproved(User $user, string $deviceId): UserDevice
    {
        /** @var UserDevice|null $device */
        $device = $user->devices()
            ->where('device_id', $deviceId)
            ->whereNull('deleted_at')
            ->first();

        if ($device === null) {
            $this->deny('DEVICE_NOT_APPROVED', 'Device is not approved for playback.', 403);
        }

        if ((int) $device->status !== 0 || $device->approved_at === null) {
            $this->deny('DEVICE_NOT_APPROVED', 'Device is not approved for playback.', 403);
        }

        return $device;
    }

    private function assertConcurrencyFree(User $user): void
    {
        $active = $user->playbackSessions()
            ->whereNull('ended_at')
            ->whereNull('deleted_at')
            ->first();

        if ($active !== null) {
            $this->deny('CONCURRENT_PLAYBACK', 'Another playback session is active.', 409);
        }
    }

    private function assertWithinViewLimit(User $user, Video $video, Course $course, ?int $pivotOverride): void
    {
        $limit = $this->resolveViewLimit($user, $video, $course, $pivotOverride);

        if ($limit === null) {
            return;
        }

        $fullPlays = $video->playbackSessions()
            ->where('user_id', $user->id)
            ->where('is_full_play', true)
            ->whereNull('deleted_at')
            ->count();

        if ($fullPlays >= $limit) {
            $this->deny('VIEW_LIMIT_EXCEEDED', 'View limit exceeded.', 403);
        }
    }

    private function resolveViewLimit(User $user, Video $video, Course $course, ?int $pivotOverride): ?int
    {
        /** @var StudentSetting|null $studentSetting */
        $studentSetting = $user->studentSetting;
        $studentLimit = $studentSetting?->settings['view_limit'] ?? null;
        if (is_numeric($studentLimit)) {
            return (int) $studentLimit;
        }

        /** @var VideoSetting|null $videoSetting */
        $videoSetting = $video->setting;
        $videoLimit = $videoSetting?->settings['view_limit'] ?? null;
        if (is_numeric($videoLimit)) {
            return (int) $videoLimit;
        }

        if (is_numeric($pivotOverride)) {
            return (int) $pivotOverride;
        }

        /** @var CourseSetting|null $courseSetting */
        $courseSetting = $course->setting;
        $courseLimit = $courseSetting?->settings['view_limit'] ?? null;
        if (is_numeric($courseLimit)) {
            return (int) $courseLimit;
        }

        /** @var CenterSetting|null $centerSetting */
        $centerSetting = $course->center?->setting;
        $centerLimit = $centerSetting?->settings['default_view_limit'] ?? null;
        if (is_numeric($centerLimit)) {
            return (int) $centerLimit;
        }

        $centerDefault = $course->center?->default_view_limit;

        return is_numeric($centerDefault) ? (int) $centerDefault : null;
    }

    private function signPlaybackUrl(Video $video): string
    {
        $expires = time() + 120;
        $sourceUrl = $video->source_url ?? '';
        $path = parse_url($sourceUrl, PHP_URL_PATH) ?? $sourceUrl;
        $key = (string) config('services.bunny.signing_key', env('BUNNY_SIGNING_KEY', 'secret'));
        $token = hash_hmac('sha256', $path.$expires, $key);

        $separator = str_contains($sourceUrl, '?') ? '&' : '?';

        return $sourceUrl.$separator.'token='.$token.'&expires='.$expires;
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
