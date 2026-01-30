<?php

declare(strict_types=1);

namespace App\Services\Courses;

use App\Enums\VideoUploadStatus;
use App\Exceptions\CenterMismatchException;
use App\Exceptions\NotFoundException;
use App\Filters\Mobile\CourseFilters;
use App\Models\Center;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Models\Video;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ExploreCourseService
{
    /**
     * @return LengthAwarePaginator<Course>
     */
    public function explore(User $student, CourseFilters $filters): LengthAwarePaginator
    {
        $query = Course::query()
            ->where('status', Course::STATUS_PUBLISHED)
            ->where('is_published', true)
            ->with(['center', 'category', 'instructors'])
            ->withExists([
                'enrollments as is_enrolled' => function ($query) use ($student): void {
                    $query->where('user_id', $student->id)
                        ->where('status', Enrollment::STATUS_ACTIVE)
                        ->whereNull('deleted_at');
                },
            ]);

        if (is_numeric($student->center_id)) {
            $query->where('center_id', (int) $student->center_id);
        } else {
            $query->whereHas('center', function ($query): void {
                $query->where('type', Center::TYPE_UNBRANDED);
            });
        }

        if ($filters->categoryId !== null) {
            $query->where('category_id', $filters->categoryId);
        }

        if ($filters->isFeatured !== null) {
            $query->where('is_featured', $filters->isFeatured);
        }

        if ($filters->instructorId !== null) {
            $query->whereHas('instructors', function ($query) use ($filters): void {
                $query->where('instructors.id', $filters->instructorId);
            });
        }

        if ($filters->enrolled === true) {
            $query->whereHas('enrollments', function ($query) use ($student): void {
                $query->where('user_id', $student->id)
                    ->where('status', Enrollment::STATUS_ACTIVE)
                    ->whereNull('deleted_at');
            });
        } elseif ($filters->enrolled === false) {
            $query->whereDoesntHave('enrollments', function ($query) use ($student): void {
                $query->where('user_id', $student->id)
                    ->where('status', Enrollment::STATUS_ACTIVE)
                    ->whereNull('deleted_at');
            });
        }

        if ($filters->publishFrom !== null) {
            $query->where('publish_at', '>=', $filters->publishFrom);
        }

        if ($filters->publishTo !== null) {
            $query->where('publish_at', '<=', $filters->publishTo);
        }

        $query->whereDoesntHave('videos', function ($query): void {
            $query->where('encoding_status', '!=', VideoUploadStatus::Ready->value)
                ->orWhere('lifecycle_status', '!=', Video::LIFECYCLE_READY)
                ->orWhere(function ($query): void {
                    $query->whereNotNull('upload_session_id')
                        ->whereHas('uploadSession', function ($query): void {
                            $query->where('upload_status', '!=', VideoUploadStatus::Ready->value);
                        });
                });
        });

        return $query->paginate(
            $filters->perPage,
            ['*'],
            'page',
            $filters->page
        );
    }

    public function show(User $student, Course $course): Course
    {
        if ((int) $course->status !== Course::STATUS_PUBLISHED || $course->is_published !== true) {
            $this->notFound();
        }

        $course->load([
            'center',
            'category',
            'instructors',
            'sections.videos',
            'sections.videos.uploadSession',
            'sections.pdfs',
            'videos',
            'videos.uploadSession',
            'pdfs',
        ]);

        if (is_numeric($student->center_id)) {
            if ((int) $course->center_id !== (int) $student->center_id) {
                $this->centerMismatch();
            }
        } else {
            $isUnbranded = Center::query()
                ->where('id', $course->center_id)
                ->where('type', Center::TYPE_UNBRANDED)
                ->exists();

            if (! $isUnbranded) {
                $this->centerMismatch();
            }
        }

        /** @var Enrollment|null $enrollment */
        $enrollment = Enrollment::query()
            ->where('user_id', $student->id)
            ->where('course_id', $course->id)
            ->whereNull('deleted_at')
            ->first();

        $course->setAttribute('is_enrolled', $enrollment !== null && $enrollment->status === Enrollment::STATUS_ACTIVE);
        $course->setAttribute('enrollment_status', $enrollment?->statusLabel());
        $this->filterReadyVideos($course);

        return $course;
    }

    private function filterReadyVideos(Course $course): void
    {
        $course->setRelation('videos', $course->videos->filter(fn (Video $video): bool => $this->isVideoReady($video))->values());

        foreach ($course->sections as $section) {
            $section->setRelation(
                'videos',
                $section->videos->filter(fn (Video $video): bool => $this->isVideoReady($video))->values()
            );
        }
    }

    private function isVideoReady(Video $video): bool
    {
        if ($video->encoding_status !== VideoUploadStatus::Ready || (int) $video->lifecycle_status !== Video::LIFECYCLE_READY) {
            return false;
        }

        $session = $video->uploadSession;
        if ($session !== null && $session->upload_status !== VideoUploadStatus::Ready) {
            return false;
        }

        return true;
    }

    private function centerMismatch(): void
    {
        throw new CenterMismatchException('Course does not belong to your center.', 403);
    }

    private function notFound(): void
    {
        throw new NotFoundException('Course not found.', 404);
    }
}
