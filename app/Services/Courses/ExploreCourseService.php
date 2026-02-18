<?php

declare(strict_types=1);

namespace App\Services\Courses;

use App\Enums\CenterType;
use App\Enums\CourseStatus;
use App\Enums\VideoLifecycleStatus;
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
use Illuminate\Support\Carbon;

class ExploreCourseService
{
    /**
     * @return LengthAwarePaginator<Course>
     */
    public function explore(User $student, CourseFilters $filters): LengthAwarePaginator
    {
        $query = Course::query()
            ->published()
            ->with(['center', 'category', 'instructors'])
            ->withEnrollmentMeta($student)
            ->visibleToStudent($student);

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
            $query->enrolledBy($student);
        } elseif ($filters->enrolled === false) {
            $query->notEnrolledBy($student);
        }

        if ($filters->publishFrom !== null) {
            $query->where('publish_at', '>=', Carbon::parse($filters->publishFrom)->startOfDay());
        }

        if ($filters->publishTo !== null) {
            $query->where('publish_at', '<=', Carbon::parse($filters->publishTo)->endOfDay());
        }

        $query->whereDoesntHave('videos', function ($query): void {
            $query->where('encoding_status', '!=', VideoUploadStatus::Ready->value)
                ->orWhere('lifecycle_status', '!=', VideoLifecycleStatus::Ready->value)
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
        $course = Course::query()
            ->withEnrollmentMeta($student, true)
            ->whereKey($course->id)
            ->firstOrFail();

        if ($course->status !== CourseStatus::Published || $course->is_published !== true) {
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

        if (($course->center?->status ?? null) !== Center::STATUS_ACTIVE) {
            $this->centerMismatch();
        }

        if (is_numeric($student->center_id)) {
            if ((int) $course->center_id !== (int) $student->center_id) {
                $this->centerMismatch();
            }
        } else {
            $isUnbranded = Center::query()
                ->where('id', $course->center_id)
                ->where('type', CenterType::Unbranded->value)
                ->where('status', Center::STATUS_ACTIVE->value)
                ->exists();

            if (! $isUnbranded) {
                $this->centerMismatch();
            }
        }

        $activeStatus = $course->active_enrollment_status ?? null;
        $latestStatus = $course->latest_enrollment_status ?? null;
        $statusValue = $activeStatus ?? $latestStatus;

        $course->setAttribute('is_enrolled', (bool) ($course->is_enrolled ?? false));
        $course->setAttribute(
            'enrollment_status',
            $statusValue !== null ? (Enrollment::statusLabels()[$statusValue] ?? 'UNKNOWN') : null
        );
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
        if ($video->encoding_status !== VideoUploadStatus::Ready || $video->lifecycle_status !== VideoLifecycleStatus::Ready) {
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
