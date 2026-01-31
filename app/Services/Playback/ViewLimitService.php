<?php

declare(strict_types=1);

namespace App\Services\Playback;

use App\Exceptions\DomainException;
use App\Models\Course;
use App\Models\ExtraViewRequest;
use App\Models\PlaybackSession;
use App\Models\User;
use App\Models\Video;
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
        $fullPlays = $this->countFullPlays($user, $video);

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

        $used = $this->countFullPlays($user, $video);

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

        $settings = $this->settingsResolver->resolve($user, $video, $course, $course->center);
        $limit = $settings['view_limit'] ?? null;

        if (! is_numeric($limit) || (int) $limit === 0) {
            return null;
        }

        $extra = $this->resolveExtraViews($user, $video);

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
        $settings = $this->settingsResolver->resolve($user, $video, $course, $course->center);
        $limit = $settings['view_limit'] ?? null;

        if (! is_numeric($limit) && is_numeric($pivotOverride)) {
            $limit = $pivotOverride;
        }

        if (! is_numeric($limit)) {
            $limit = 0;
        }

        $extra = $this->resolveExtraViews($user, $video);

        return (int) $limit + $extra;
    }

    private function resolveExtraViews(User $user, Video $video): int
    {
        $settings = $user->studentSetting?->settings ?? [];
        $extraViews = $settings['extra_views'][$video->id] ?? null;

        $base = is_numeric($extraViews) ? (int) $extraViews : 0;

        $approved = ExtraViewRequest::query()
            ->approvedForUserAndVideo($user, $video)
            ->sum('granted_views');

        return $base + (int) $approved;
    }

    private function countFullPlays(User $user, Video $video): int
    {
        return PlaybackSession::query()
            ->fullPlaysForUserAndVideo($user, $video)
            ->notDeleted()
            ->count();
    }
}
