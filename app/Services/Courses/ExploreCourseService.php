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
use App\Services\Settings\PolicySettingsService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class ExploreCourseService
{
    public function __construct(
        private readonly PolicySettingsService $policySettingsService
    ) {}

    /**
     * Explore courses for authenticated students or guest users.
     *
     * @return LengthAwarePaginator<Course>
     */
    public function explore(?User $student, CourseFilters $filters): LengthAwarePaginator
    {
        $query = Course::query()
            ->published()
            ->with(['center', 'category', 'primaryInstructor', 'instructors']);

        // Apply student-specific scopes for authenticated users
        if ($student instanceof User) {
            $query->withEnrollmentMeta($student)
                ->visibleToStudent($student)
                ->matchingStudentEducation($student);
        } else {
            // Guest user - show published courses from active centers
            $this->applyGuestVisibility($query);
        }

        if ($filters->categoryId !== null) {
            $query->where('category_id', $filters->categoryId);
        }

        if ($filters->isFeatured !== null) {
            $query->where('is_featured', $filters->isFeatured);
        }

        if ($filters->instructorId !== null) {
            $query->where(function ($query) use ($filters): void {
                $query->where('primary_instructor_id', $filters->instructorId)
                    ->orWhereHas('instructors', function ($query) use ($filters): void {
                        $query->where('instructors.id', $filters->instructorId);
                    });
            });
        }

        // Enrollment filters only apply to authenticated users
        if ($student instanceof User) {
            if ($filters->enrolled === true) {
                $query->accessibleBy($student);
            } elseif ($filters->enrolled === false) {
                $query->notAccessibleBy($student);
            }
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

    /**
     * Show course details for authenticated students or guest users.
     */
    public function show(?User $student, Course $course): Course
    {
        $query = Course::query()->whereKey($course->id);

        if ($student instanceof User) {
            $query->withEnrollmentMeta($student, true)
                ->where(function (Builder $visibilityQuery) use ($student): void {
                    $visibilityQuery->matchingStudentEducation($student)
                        ->orWhere(function (Builder $accessQuery) use ($student): void {
                            $accessQuery->accessibleBy($student);
                        });
                });
        } else {
            // Guest user - apply guest education filter
            $this->applyGuestEducationFilter();
        }

        $course = $query->first();

        if (! $course instanceof Course) {
            $this->notFound();
        }

        if ($course->status !== CourseStatus::Published || $course->is_published !== true) {
            $this->notFound();
        }

        $course->load([
            'center',
            'category',
            'primaryInstructor',
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

        // Validate center access for authenticated students
        if ($student instanceof User) {
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
        } else {
            // Guest user - course center must allow guest browsing
            // Note: center is guaranteed to be non-null after the check on line 137
            if ($course->center === null || ! $this->policySettingsService->centerAllowsGuestBrowsing($course->center)) {
                $this->centerMismatch();
            }
        }

        // Set enrollment attributes
        if ($student instanceof User) {
            $activeStatus = $course->active_enrollment_status ?? null;
            $latestStatus = $course->latest_enrollment_status ?? null;
            $statusValue = $activeStatus ?? $latestStatus;

            $course->setAttribute('is_enrolled', (bool) ($course->is_enrolled ?? false));
            $course->setAttribute(
                'enrollment_status',
                $statusValue !== null ? (Enrollment::statusLabels()[$statusValue] ?? 'UNKNOWN') : null
            );
        } else {
            // Guest users are never enrolled
            $course->setAttribute('is_enrolled', false);
            $course->setAttribute('enrollment_status', null);
        }

        $this->filterReadyVideos($course);

        return $course;
    }

    /**
     * Apply visibility filter for guest users.
     * Guests can see all published courses from active centers that allow guest browsing.
     * Education filters are not applied to guests.
     *
     * @param  Builder<Course>  $query
     */
    private function applyGuestVisibility(Builder $query): void
    {
        $policySettingsService = $this->policySettingsService;

        $query->whereHas('center', function (Builder $query) use ($policySettingsService): void {
            $query->where('status', Center::STATUS_ACTIVE->value);
            $policySettingsService->applyGuestBrowsingFilter($query);
        });
    }

    /**
     * Apply education filter for guest users.
     * Guests see all courses without education restrictions.
     */
    private function applyGuestEducationFilter(): void
    {
        // No education filter for guests - they can see all published courses
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
