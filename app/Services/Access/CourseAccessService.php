<?php

declare(strict_types=1);

namespace App\Services\Access;

use App\Exceptions\DomainException;
use App\Models\Center;
use App\Models\Course;
use App\Models\Video;
use App\Support\ErrorCodes;

class CourseAccessService
{
    public function assertCourseInCenter(
        Course $course,
        Center $center,
        string $message = 'Course not found.',
        string $code = ErrorCodes::NOT_FOUND,
        int $status = 404
    ): void {
        if ((int) $course->center_id !== (int) $center->id) {
            throw new DomainException($message, $code, $status);
        }
    }

    public function isVideoInCourse(Course $course, Video $video): bool
    {
        return $course->videos()
            ->where('videos.id', $video->id)
            ->wherePivotNull('deleted_at')
            ->exists();
    }

    public function getVideoPivotOrFail(
        Course $course,
        Video $video,
        string $message = 'Video not available for this course.',
        string $code = ErrorCodes::VIDEO_NOT_IN_COURSE,
        int $status = 404
    ): Video {
        $pivot = $course->videos()
            ->where('videos.id', $video->id)
            ->wherePivotNull('deleted_at')
            ->first();

        if (! $pivot instanceof Video) {
            throw new DomainException($message, $code, $status);
        }

        return $pivot;
    }

    public function assertVideoInCourse(
        Course $course,
        Video $video,
        string $message = 'Video not available for this course.',
        string $code = ErrorCodes::VIDEO_NOT_IN_COURSE,
        int $status = 404
    ): void {
        if (! $this->isVideoInCourse($course, $video)) {
            throw new DomainException($message, $code, $status);
        }
    }
}
