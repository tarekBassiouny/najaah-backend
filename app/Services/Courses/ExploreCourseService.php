<?php

declare(strict_types=1);

namespace App\Services\Courses;

use App\Filters\Mobile\CourseFilters;
use App\Models\Center;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Models\Video;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ExploreCourseService
{
    /**
     * @return LengthAwarePaginator<Course>
     */
    public function explore(User $student, CourseFilters $filters): LengthAwarePaginator
    {
        $query = Course::query()
            ->where('status', 3)
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
                $query->where('type', 0);
            });
        }

        if ($filters->categoryId !== null) {
            $query->where('category_id', $filters->categoryId);
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
            $query->where('encoding_status', '!=', 3)
                ->orWhere('lifecycle_status', '!=', 2)
                ->orWhere(function ($query): void {
                    $query->whereNotNull('upload_session_id')
                        ->whereHas('uploadSession', function ($query): void {
                            $query->where('upload_status', '!=', 3);
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
        if ((int) $course->status !== 3 || $course->is_published !== true) {
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
                ->where('type', 0)
                ->exists();

            if (! $isUnbranded) {
                $this->centerMismatch();
            }
        }

        $isEnrolled = Enrollment::query()
            ->where('user_id', $student->id)
            ->where('course_id', $course->id)
            ->where('status', Enrollment::STATUS_ACTIVE)
            ->whereNull('deleted_at')
            ->exists();

        $course->setAttribute('is_enrolled', $isEnrolled);
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
        if ((int) $video->encoding_status !== 3 || (int) $video->lifecycle_status !== 2) {
            return false;
        }

        $session = $video->uploadSession;
        if ($session !== null && (int) $session->upload_status !== 3) {
            return false;
        }

        return true;
    }

    private function centerMismatch(): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'error' => [
                'code' => 'CENTER_MISMATCH',
                'message' => 'Course does not belong to your center.',
            ],
        ], 403));
    }

    private function notFound(): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'error' => [
                'code' => 'NOT_FOUND',
                'message' => 'Course not found.',
            ],
        ], 404));
    }
}
