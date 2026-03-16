<?php

declare(strict_types=1);

namespace App\Services\Playback;

use App\Exceptions\DomainException;
use App\Models\Course;
use App\Models\ExtraViewRequest;
use App\Models\PlaybackSession;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoAccess;
use App\Services\Playback\Contracts\ViewLimitServiceInterface;
use App\Services\Settings\Contracts\SettingsResolverServiceInterface;
use App\Support\ErrorCodes;

class ViewLimitService implements ViewLimitServiceInterface
{
    public function __construct(
        private readonly SettingsResolverServiceInterface $settingsResolver
    ) {}

    public function remaining(User $user, Video $video, Course $course, ?int $pivotOverride = null): int
    {
        $limit = $this->resolveLimit($user, $video, $course, $pivotOverride);
        $fullPlays = $this->countFullPlays($user, $video, $course);

        return $limit - $fullPlays;
    }

    public function assertWithinLimit(User $user, Video $video, Course $course, ?int $pivotOverride = null): void
    {
        if ($this->remaining($user, $video, $course, $pivotOverride) <= 0) {
            throw new DomainException('View limit exceeded.', ErrorCodes::VIEW_LIMIT_EXCEEDED, 403);
        }
    }

    /**
     * Get remaining views for a user/video combination.
     *
     * @return int|null Remaining views, or null if unlimited
     */
    public function getRemainingViews(User $user, Video $video, ?Course $course = null): ?int
    {
        $limit = $this->getEffectiveLimit($user, $video, $course);

        if ($limit === null) {
            return null;
        }

        $used = $this->countFullPlays($user, $video, $course);

        return max(0, $limit - $used);
    }

    /**
     * Get the effective view limit for a user/video combination.
     *
     * @return int|null View limit, or null if unlimited
     */
    public function getEffectiveLimit(User $user, Video $video, ?Course $course = null): ?int
    {
        if ($course === null) {
            return null;
        }

        // For video code access model, use total_view_limit from VideoAccess
        if ($course->usesVideoCodeAccess()) {
            $limit = $this->resolveVideoCodeLimit($user, $video, $course);

            return $limit > 0 ? $limit : null;
        }

        // For enrollment access model, use settings hierarchy
        $settings = $this->settingsResolver->resolve($user, $video, $course, $course->center);
        $limit = $settings['view_limit'] ?? null;

        if (! is_numeric($limit) || (int) $limit === 0) {
            return null;
        }

        $extra = $this->resolveExtraViews($user, $video, $course);

        return (int) $limit + $extra;
    }

    /**
     * Check if video is locked (no remaining views).
     */
    public function isLocked(User $user, Video $video, ?Course $course = null): bool
    {
        $remaining = $this->getRemainingViews($user, $video, $course);

        return $remaining !== null && $remaining <= 0;
    }

    /**
     * Resolve the base view limit without student overrides.
     */
    public function resolveViewLimit(Video $video, ?Course $course = null): ?int
    {
        if ($course === null) {
            return null;
        }

        $settings = $this->settingsResolver->resolve(null, $video, $course, $course->center);
        $limit = $settings['view_limit'] ?? null;

        if (! is_numeric($limit) || (int) $limit === 0) {
            return null;
        }

        return (int) $limit;
    }

    private function resolveLimit(User $user, Video $video, Course $course, ?int $pivotOverride): int
    {
        // For video code access model, use total_view_limit from VideoAccess
        if ($course->usesVideoCodeAccess()) {
            return $this->resolveVideoCodeLimit($user, $video, $course);
        }

        // For enrollment access model, use settings hierarchy
        $settings = $this->settingsResolver->resolve($user, $video, $course, $course->center);
        $limit = $settings['view_limit'] ?? null;

        if (! is_numeric($limit) && is_numeric($pivotOverride)) {
            $limit = $pivotOverride;
        }

        if (! is_numeric($limit)) {
            $limit = 0;
        }

        $extra = $this->resolveExtraViews($user, $video, $course);

        return (int) $limit + $extra;
    }

    /**
     * Resolve view limit for video code access model.
     * Uses total_view_limit from VideoAccess record (stacked from multiple code redemptions).
     */
    private function resolveVideoCodeLimit(User $user, Video $video, Course $course): int
    {
        /** @var VideoAccess|null $access */
        $access = VideoAccess::query()
            ->where('user_id', $user->id)
            ->where('video_id', $video->id)
            ->where('course_id', $course->id)
            ->active()
            ->first();

        if ($access === null) {
            return 0;
        }

        return $access->total_view_limit ?? 0;
    }

    private function resolveExtraViews(User $user, Video $video, Course $course): int
    {
        $settings = $user->studentSetting?->settings ?? [];

        $courseScopedExtraViews = data_get($settings, sprintf('extra_views_by_course.%d.%d', $course->id, $video->id));
        $legacyExtraViews = $this->canUseLegacyExtraViews($video, $course)
            ? data_get($settings, sprintf('extra_views.%d', $video->id))
            : null;
        $extraViews = is_numeric($courseScopedExtraViews) ? $courseScopedExtraViews : $legacyExtraViews;

        $base = is_numeric($extraViews) ? (int) $extraViews : 0;

        $approved = ExtraViewRequest::query()
            ->approvedForUserAndCourseVideo($user, $video, $course)
            ->sum('granted_views');

        return $base + (int) $approved;
    }

    private function countFullPlays(User $user, Video $video, Course $course): int
    {
        return PlaybackSession::query()
            ->fullPlaysForUserAndCourseVideo($user, $video, $course)
            ->notDeleted()
            ->count();
    }

    private function canUseLegacyExtraViews(Video $video, Course $course): bool
    {
        if ($video->relationLoaded('courses')) {
            return $video->courses
                ->pluck('id')
                ->unique()
                ->values()
                ->all() === [$course->id];
        }

        return $video->courses()
            ->whereKeyNot($course->id)
            ->wherePivotNull('deleted_at')
            ->doesntExist();
    }
}
