<?php

declare(strict_types=1);

namespace App\Http\Resources\Mobile;

use App\Enums\VideoAccessCodeStatus;
use App\Enums\VideoAccessRequestStatus;
use App\Models\Course;
use App\Models\Pivots\CourseVideo;
use App\Models\PlaybackSession;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoAccess;
use App\Models\VideoAccessCode;
use App\Models\VideoAccessRequest;
use App\Services\Playback\Contracts\ViewLimitServiceInterface;
use App\Services\VideoAccess\Contracts\VideoApprovalServiceInterface;
use App\Services\Videos\VideoThumbnailUrlResolver;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Video
 */
class CourseVideoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Video $video */
        $video = $this->resource;
        /** @var CourseVideo|null $pivot */
        $pivot = $video->pivot instanceof CourseVideo ? $video->pivot : null;
        $course = $this->resolveCourse($request, $video, $pivot);

        $requiresRedemption = $this->requiresRedemption($course);
        $redemptionData = $this->resolveRedemptionStatus($request, $video, $course, $requiresRedemption);

        $isLocked = ! $redemptionData['has_redeemed'];

        if (! $isLocked && ! (bool) ($pivot?->visible ?? true)) {
            $isLocked = true;
        }

        if ($pivot !== null && ! $isLocked) {
            $isLocked = $this->isViewLimitExceeded($request, $video, $pivot);
        }

        $lessonStats = $this->resolveLessonStats($request, $video, $course);
        $thumbnail = app(VideoThumbnailUrlResolver::class)->resolve($video->effectiveThumbnailPath());

        return [
            'id' => $video->id,
            'title' => $video->translate('title'),
            'tags' => $video->tags,
            'duration' => $video->duration_seconds,
            'duration_seconds' => $video->duration_seconds,
            'thumbnail' => $thumbnail,
            'requires_redemption' => $requiresRedemption,
            'has_redeemed' => $redemptionData['has_redeemed'],
            'is_locked' => $isLocked,
            'view_limit' => $lessonStats['view_limit'],
            'remaining_views' => $lessonStats['remaining_views'],
            'full_plays' => $lessonStats['full_plays'],
            'watch_duration_seconds' => $lessonStats['watch_duration_seconds'],
            'access_status' => $redemptionData['access_status'],
            'pending_request_id' => $redemptionData['pending_request_id'],
            'updated_at' => $video->updated_at,
        ];
    }

    private function isViewLimitExceeded(Request $request, Video $video, CourseVideo $pivot): bool
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user === null) {
            return false;
        }

        $course = $pivot->course;

        if ($course === null) {
            return false;
        }

        /** @var ViewLimitServiceInterface $viewLimitService */
        $viewLimitService = app(ViewLimitServiceInterface::class);

        return $viewLimitService->isLocked($user, $video, $course);
    }

    /**
     * @return array{has_redeemed:bool,access_status:?string,pending_request_id:?int}
     */
    private function resolveRedemptionStatus(Request $request, Video $video, ?Course $course, bool $requiresRedemption): array
    {
        /** @var User|null $user */
        $user = $request->user();

        if (! $requiresRedemption) {
            return [
                'has_redeemed' => true,
                'access_status' => null,
                'pending_request_id' => null,
            ];
        }

        if (! $user instanceof User || ! $course instanceof Course) {
            return [
                'has_redeemed' => false,
                'access_status' => 'locked',
                'pending_request_id' => null,
            ];
        }

        // For video_code access model, only check VideoAccess (no approval flow)
        if ($course->usesVideoCodeAccess()) {
            return $this->resolveVideoCodeRedemptionStatus($user, $video, $course);
        }

        // For enrollment access model, use the full approval flow
        return $this->resolveEnrollmentRedemptionStatus($user, $video, $course);
    }

    /**
     * Resolve redemption status for video_code access model.
     * Only checks for VideoAccess - no pending requests or approval codes.
     *
     * @return array{has_redeemed:bool,access_status:?string,pending_request_id:?int}
     */
    private function resolveVideoCodeRedemptionStatus(User $user, Video $video, Course $course): array
    {
        $hasAccess = VideoAccess::query()
            ->forUserAndVideo($user->id, $video->id)
            ->where('course_id', $course->id)
            ->active()
            ->exists();

        return [
            'has_redeemed' => $hasAccess,
            'access_status' => $hasAccess ? 'granted' : 'locked',
            'pending_request_id' => null,
        ];
    }

    /**
     * Resolve redemption status for enrollment access model.
     * Uses the full approval flow with VideoAccessRequests and VideoAccessCodes.
     *
     * @return array{has_redeemed:bool,access_status:?string,pending_request_id:?int}
     */
    private function resolveEnrollmentRedemptionStatus(User $user, Video $video, Course $course): array
    {
        $hasRedeemed = VideoAccess::query()
            ->forUserAndVideo($user->id, $video->id)
            ->where('course_id', $course->id)
            ->active()
            ->exists();

        if ($hasRedeemed) {
            return [
                'has_redeemed' => true,
                'access_status' => 'granted',
                'pending_request_id' => null,
            ];
        }

        /** @var VideoAccessRequest|null $pendingRequest */
        $pendingRequest = VideoAccessRequest::query()
            ->where('user_id', $user->id)
            ->where('video_id', $video->id)
            ->where('course_id', $course->id)
            ->where('status', VideoAccessRequestStatus::Pending->value)
            ->orderByDesc('id')
            ->first();

        if ($pendingRequest instanceof VideoAccessRequest) {
            return [
                'has_redeemed' => false,
                'access_status' => 'pending',
                'pending_request_id' => $pendingRequest->id,
            ];
        }

        /** @var VideoAccessCode|null $activeCode */
        $activeCode = VideoAccessCode::query()
            ->where('user_id', $user->id)
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
                    'has_redeemed' => false,
                    'access_status' => 'approved',
                    'pending_request_id' => null,
                ];
            }
        }

        $wasRejected = VideoAccessRequest::query()
            ->where('user_id', $user->id)
            ->where('video_id', $video->id)
            ->where('course_id', $course->id)
            ->where('status', VideoAccessRequestStatus::Rejected->value)
            ->exists();

        if ($wasRejected) {
            return [
                'has_redeemed' => false,
                'access_status' => 'rejected',
                'pending_request_id' => null,
            ];
        }

        return [
            'has_redeemed' => false,
            'access_status' => 'locked',
            'pending_request_id' => null,
        ];
    }

    private function requiresRedemption(?Course $course): bool
    {
        if (! $course instanceof Course) {
            return false;
        }

        // Video code access model always requires redemption (via code)
        if ($course->usesVideoCodeAccess()) {
            return true;
        }

        // Enrollment access model uses the approval setting
        if ($course->center === null) {
            return false;
        }

        /** @var VideoApprovalServiceInterface $videoApprovalService */
        $videoApprovalService = app(VideoApprovalServiceInterface::class);

        return $videoApprovalService->requiresApproval($course->center, $course);
    }

    private function resolveCourse(Request $request, Video $video, ?CourseVideo $pivot): ?Course
    {
        $routeCourse = $request->route('course');
        if ($routeCourse instanceof Course) {
            return $routeCourse;
        }

        if ($pivot instanceof CourseVideo && $pivot->relationLoaded('course')) {
            return $pivot->course;
        }

        if ($video->relationLoaded('courses')) {
            if ($video->courses->count() !== 1) {
                return null;
            }

            /** @var Course|null $course */
            $course = $video->courses->first();

            return $course;
        }

        return null;
    }

    /**
     * @return array{
     *     view_limit:int|null,
     *     remaining_views:int|null,
     *     full_plays:int,
     *     watch_duration_seconds:int
     * }
     */
    private function resolveLessonStats(Request $request, Video $video, ?Course $course): array
    {
        /** @var User|null $user */
        $user = $request->user();

        if (! $user instanceof User || ! $course instanceof Course) {
            return [
                'view_limit' => null,
                'remaining_views' => null,
                'full_plays' => 0,
                'watch_duration_seconds' => 0,
            ];
        }

        /** @var ViewLimitServiceInterface $viewLimitService */
        $viewLimitService = app(ViewLimitServiceInterface::class);

        $statsMap = $this->resolveLessonStatsMap($request, $course, $user, (int) $video->id);
        $videoStats = $statsMap[$video->id] ?? [
            'full_plays' => 0,
            'watch_duration_seconds' => 0,
        ];

        return [
            'view_limit' => $viewLimitService->getEffectiveLimit($user, $video, $course),
            'remaining_views' => $viewLimitService->getRemainingViews($user, $video, $course),
            'full_plays' => (int) ($videoStats['full_plays'] ?? 0),
            'watch_duration_seconds' => (int) ($videoStats['watch_duration_seconds'] ?? 0),
        ];
    }

    /**
     * @return array<int, array{full_plays:int,watch_duration_seconds:int}>
     */
    private function resolveLessonStatsMap(Request $request, Course $course, User $user, int $fallbackVideoId): array
    {
        /** @var array<string, array<int, array{full_plays:int,watch_duration_seconds:int}>> $cache */
        $cache = $request->attributes->get('course_video_lesson_stats_cache', []);
        if (! is_array($cache)) {
            $cache = [];
        }

        $cacheKey = $user->id.':'.$course->id;
        if (array_key_exists($cacheKey, $cache)) {
            return $cache[$cacheKey];
        }

        $videoIds = $this->resolveCourseVideoIds($course);
        if ($videoIds === []) {
            $videoIds = [$fallbackVideoId];
        }

        $stats = array_fill_keys($videoIds, [
            'full_plays' => 0,
            'watch_duration_seconds' => 0,
        ]);

        $rows = PlaybackSession::query()
            ->selectRaw(
                'video_id, SUM(CASE WHEN is_full_play = 1 THEN 1 ELSE 0 END) as full_plays, COALESCE(SUM(watch_duration), 0) as watch_duration_seconds'
            )
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->whereIn('video_id', $videoIds)
            ->whereNull('deleted_at')
            ->groupBy('video_id')
            ->get();

        foreach ($rows as $row) {
            $videoId = (int) $row->video_id;
            $stats[$videoId] = [
                'full_plays' => (int) ($row->full_plays ?? 0),
                'watch_duration_seconds' => (int) ($row->watch_duration_seconds ?? 0),
            ];
        }

        $cache[$cacheKey] = $stats;
        $request->attributes->set('course_video_lesson_stats_cache', $cache);

        return $stats;
    }

    /**
     * @return array<int>
     */
    private function resolveCourseVideoIds(Course $course): array
    {
        $videoIds = [];

        if ($course->relationLoaded('videos')) {
            foreach ($course->videos as $video) {
                $videoIds[] = (int) $video->id;
            }
        }

        if ($course->relationLoaded('sections')) {
            foreach ($course->sections as $section) {
                if (! $section->relationLoaded('videos')) {
                    continue;
                }

                foreach ($section->videos as $video) {
                    $videoIds[] = (int) $video->id;
                }
            }
        }

        return array_values(array_unique($videoIds));
    }
}
