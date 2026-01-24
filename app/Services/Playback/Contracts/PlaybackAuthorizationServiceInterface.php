<?php

declare(strict_types=1);

namespace App\Services\Playback\Contracts;

use App\Models\Center;
use App\Models\Course;
use App\Models\PlaybackSession;
use App\Models\User;
use App\Models\UserDevice;
use App\Models\Video;

interface PlaybackAuthorizationServiceInterface
{
    public function assertCanStartPlayback(User $student, Center $center, Course $course, Video $video): void;

    public function assertCanRefreshToken(
        User $student,
        Center $center,
        Course $course,
        Video $video,
        PlaybackSession $session
    ): void;

    public function assertCanUpdateProgress(
        User $student,
        Center $center,
        Course $course,
        Video $video,
        PlaybackSession $session
    ): void;

    public function getActiveDevice(): UserDevice;
}
