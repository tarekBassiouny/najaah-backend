<?php

declare(strict_types=1);

namespace App\Services\Playback;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Section;
use App\Models\User;
use App\Models\UserDevice;
use App\Models\Video;
use App\Services\Centers\CenterScopeService;
use App\Services\Devices\Contracts\DeviceServiceInterface;
use App\Services\Enrollments\Contracts\EnrollmentServiceInterface;
use App\Services\Logging\LogContextResolver;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;

class PlaybackAuthorizationService
{
    public function __construct(
        private readonly EnrollmentServiceInterface $enrollmentService,
        private readonly PlaybackSessionService $sessionService,
        private readonly DeviceServiceInterface $deviceService,
        private readonly ViewLimitService $viewLimitService,
        private readonly ConcurrencyService $concurrencyService,
        private readonly CenterScopeService $centerScopeService
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function authorize(User $user, Course $course, Video $video, ?Section $section, string $deviceId): array
    {
        $this->assertStudent($user);
        $enrollment = $this->enrollmentService->getActiveEnrollment($user, $course);
        if (! $enrollment instanceof Enrollment) {
            $this->deny('ENROLLMENT_REQUIRED', 'Active enrollment is required for playback.');
        }

        $this->assertCourseAccessible($course);
        $pivot = $this->assertVideoAttached($course, $video);
        $this->assertSectionVisible($course, $section);
        $this->assertCenterAccess($user, $course);
        $this->assertVideoReady($video);
        $device = $this->assertDeviceApproved($user, $deviceId);
        $this->concurrencyService->assertNoActiveSession($user, $device, $video);
        $this->viewLimitService->assertWithinLimit($user, $video, $course, $pivot->view_limit_override);

        $this->sessionService->startSession($user, $video, $device);
        $course->loadMissing('center');
        $libraryId = $course->center->bunny_library_id;
        $videoId = $video->source_id;

        if (! is_string($videoId) || $videoId === '') {
            $this->deny('VIDEO_ID_MISSING', 'Video source identifier is required.');
        }

        if (! is_numeric($libraryId)) {
            $this->deny('LIBRARY_ID_MISSING', 'Library identifier is required.');
        }

        return [
            'embed_config' => [
                'video_id' => $videoId,
                'library_id' => (int) $libraryId,
            ],
        ];
    }

    private function assertStudent(User $user): void
    {
        if (! $user->is_student) {
            $this->deny('UNAUTHORIZED', 'Only students can play videos.');
        }
    }

    private function assertCourseAccessible(Course $course): void
    {
        if ((int) $course->status !== 3 || (bool) $course->trashed()) {
            $this->deny('COURSE_UNAVAILABLE', 'Course is not published.');
        }
    }

    private function assertSectionVisible(Course $course, ?Section $section): void
    {
        if ($section === null) {
            return;
        }

        if ((int) $section->course_id !== (int) $course->id || $section->trashed() || $section->visible === false) {
            $this->deny('SECTION_UNAVAILABLE', 'Section is not accessible.');
        }
    }

    private function assertVideoAttached(Course $course, Video $video): Pivot
    {
        $attached = $course->videos()
            ->where('videos.id', $video->id)
            ->wherePivotNull('deleted_at')
            ->first();

        if ($attached === null) {
            $this->deny('VIDEO_NOT_IN_COURSE', 'Video not available in this course.');
        }

        /** @var Pivot $pivot */
        $pivot = $attached->pivot;

        $visible = $pivot->getAttribute('visible');
        if ($visible === false) {
            $this->deny('VIDEO_UNAVAILABLE', 'Video is hidden.');
        }

        return $pivot;
    }

    private function assertCenterAccess(User $user, Course $course): void
    {
        $this->centerScopeService->assertSameCenter($user, $course);
    }

    private function assertVideoReady(Video $video): void
    {
        if ((int) $video->encoding_status !== 3 || (int) $video->lifecycle_status < 2) {
            $this->deny('VIDEO_NOT_READY', 'Video is not ready for playback.');
        }
    }

    private function assertDeviceApproved(User $user, string $deviceId): UserDevice
    {
        return $this->deviceService->assertActiveDevice($user, $deviceId);
    }

    /**
     * @return never
     */
    private function deny(string $code, string $message): void
    {
        Log::warning('Playback authorization denied.', $this->resolveLogContext([
            'source' => 'api',
            'code' => $code,
        ]));
        throw new HttpResponseException(response()->json([
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ], 403));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function resolveLogContext(array $overrides = []): array
    {
        return app(LogContextResolver::class)->resolve($overrides);
    }
}
