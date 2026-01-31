<?php

declare(strict_types=1);

namespace App\Services\Playback\Contracts;

use App\Models\Course;
use App\Models\User;
use App\Models\Video;

interface ViewLimitServiceInterface
{
    public function getRemainingViews(User $user, Video $video, ?Course $course = null): ?int;

    public function getEffectiveLimit(User $user, Video $video, ?Course $course = null): ?int;

    public function isLocked(User $user, Video $video, ?Course $course = null): bool;

    public function resolveViewLimit(Video $video, ?Course $course = null): ?int;
}
