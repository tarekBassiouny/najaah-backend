<?php

declare(strict_types=1);

namespace App\Services\Playback;

use App\Exceptions\DomainException;
use App\Models\Course;
use App\Models\ExtraViewRequest;
use App\Models\PlaybackSession;
use App\Models\User;
use App\Models\Video;
use App\Services\Settings\Contracts\SettingsResolverServiceInterface;
use App\Support\ErrorCodes;

class ViewLimitService
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

        $approved = ExtraViewRequest::where('user_id', $user->id)
            ->where('video_id', $video->id)
            ->where('status', ExtraViewRequest::STATUS_APPROVED)
            ->whereNull('deleted_at')
            ->sum('granted_views');

        return $base + (int) $approved;
    }

    private function countFullPlays(User $user, Video $video): int
    {
        return PlaybackSession::where('user_id', $user->id)
            ->where('video_id', $video->id)
            ->where('is_full_play', true)
            ->whereNull('deleted_at')
            ->count();
    }
}
