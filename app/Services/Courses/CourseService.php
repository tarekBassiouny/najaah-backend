<?php

declare(strict_types=1);

namespace App\Services\Courses;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Services\Centers\CenterScopeService;
use App\Services\Courses\Contracts\CourseServiceInterface;
use App\Support\Guards\RejectNonScalarInput;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CourseService implements CourseServiceInterface
{
    public function __construct(private readonly CenterScopeService $centerScopeService) {}

    /** @return LengthAwarePaginator<Course> */
    public function paginate(int $perPage = 15, ?User $actor = null): LengthAwarePaginator
    {
        $query = Course::query()
            ->with(['center', 'category', 'primaryInstructor', 'instructors'])
            ->orderByDesc('id');

        if ($actor instanceof User && ! $actor->hasRole('super_admin')) {
            $centerId = $actor->center_id;
            $this->centerScopeService->assertAdminCenterId($actor, $centerId);
            $query->where('center_id', $centerId);
        }

        return $query->paginate($perPage);
    }

    /** @param array<string, mixed> $data */
    public function create(array $data, ?User $actor = null): Course
    {
        RejectNonScalarInput::validate($data, ['title', 'description']);
        $data['title_translations'] = $data['title'] ?? '';
        $data['description_translations'] = $data['description'] ?? null;
        unset($data['title'], $data['description']);

        if (! array_key_exists('difficulty_level', $data) || ! is_numeric($data['difficulty_level'])) {
            $data['difficulty_level'] = 0;
        }

        $data['status'] = 0;
        $data['is_published'] = false;
        $data['publish_at'] = null;

        if ($actor instanceof User) {
            $centerId = isset($data['center_id']) && is_numeric($data['center_id']) ? (int) $data['center_id'] : null;
            $this->centerScopeService->assertAdminCenterId($actor, $centerId);
        }

        $course = Course::create($data);

        return $course->fresh(['center', 'category', 'primaryInstructor', 'instructors']) ?? $course;
    }

    /** @param array<string, mixed> $data */
    public function update(Course $course, array $data, ?User $actor = null): Course
    {
        RejectNonScalarInput::validate($data, ['title', 'description']);
        if (array_key_exists('title', $data)) {
            $data['title_translations'] = $data['title'];
            unset($data['title']);
        }

        if (array_key_exists('description', $data)) {
            $data['description_translations'] = $data['description'];
            unset($data['description']);
        }

        if ($actor instanceof User) {
            $this->centerScopeService->assertAdminSameCenter($actor, $course);
        }

        $course->update($data);

        return $course->fresh(['center', 'category', 'primaryInstructor', 'instructors']) ?? $course;
    }

    public function delete(Course $course, ?User $actor = null): void
    {
        if ($actor instanceof User) {
            $this->centerScopeService->assertAdminSameCenter($actor, $course);
        }

        $course->delete();
    }

    public function find(int $id, ?User $actor = null): ?Course
    {
        $query = Course::with(['center', 'category', 'primaryInstructor', 'instructors', 'sections.videos', 'sections.pdfs']);

        $course = $query->find($id);

        if ($actor instanceof User && $course !== null) {
            $this->centerScopeService->assertAdminSameCenter($actor, $course);
        }

        return $course;
    }

    /**
     * @return LengthAwarePaginator<Course>
     */
    public function search(User $student, ?string $query, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        $builder = $this->mobileBaseQuery($student);

        if ($query !== null && $query !== '') {
            $builder->where(function (Builder $q) use ($query): void {
                $q->whereTranslationLike(['title'], $query, ['en', 'ar'])
                    ->orWhereHas('instructors', function (Builder $q) use ($query): void {
                        $q->whereTranslationLike(['name'], $query, ['en', 'ar']);
                    });
            });
        }

        return $builder->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * @return Collection<int, Course>
     */
    public function fallback(User $student): Collection
    {
        $recentCourseIds = Course::query()
            ->selectRaw('courses.id, MAX(playback_sessions.started_at) as last_seen')
            ->join('course_video', 'courses.id', '=', 'course_video.course_id')
            ->join('videos', 'videos.id', '=', 'course_video.video_id')
            ->join('playback_sessions', 'playback_sessions.video_id', '=', 'videos.id')
            ->whereNull('course_video.deleted_at')
            ->where('playback_sessions.user_id', $student->id)
            ->groupBy('courses.id')
            ->orderByDesc('last_seen')
            ->limit(5)
            ->pluck('courses.id');

        $builder = $this->mobileBaseQuery($student);

        if ($recentCourseIds->isNotEmpty()) {
            $builder->whereIn('id', $recentCourseIds)
                ->orderByRaw('FIELD(id, '.$recentCourseIds->implode(',').')');

            return $builder->get();
        }

        return $builder->orderByDesc('created_at')->limit(5)->get();
    }

    /**
     * @return LengthAwarePaginator<Course>
     */
    public function enrolled(User $student, \App\Filters\Mobile\CourseFilters $filters): LengthAwarePaginator
    {
        $builder = $this->mobileBaseQuery($student)
            ->whereHas('enrollments', function (Builder $query) use ($student): void {
                $query->where('user_id', $student->id)
                    ->where('status', Enrollment::STATUS_ACTIVE)
                    ->whereNull('deleted_at');
            });

        if ($filters->categoryId !== null) {
            $builder->where('category_id', $filters->categoryId);
        }

        if ($filters->instructorId !== null) {
            $builder->whereHas('instructors', function (Builder $query) use ($filters): void {
                $query->where('instructors.id', $filters->instructorId);
            });
        }

        return $builder->paginate(
            $filters->perPage,
            ['*'],
            'page',
            $filters->page
        );
    }

    /**
     * @return Collection<int, \App\Models\Instructor>
     */
    public function enrolledGroupedByInstructor(User $student, \App\Filters\Mobile\CourseFilters $filters): Collection
    {
        $query = Course::query()
            ->where('status', 3)
            ->where('is_published', true)
            ->whereHas('enrollments', function (Builder $query) use ($student): void {
                $query->where('user_id', $student->id)
                    ->where('status', Enrollment::STATUS_ACTIVE)
                    ->whereNull('deleted_at');
            })
            ->when(is_numeric($student->center_id), function (Builder $query) use ($student): void {
                $query->where('center_id', (int) $student->center_id);
            })
            ->when(! is_numeric($student->center_id), function (Builder $query): void {
                $query->whereHas('center', fn ($q) => $q->where('type', 0));
            });

        if ($filters->categoryId !== null) {
            $query->where('category_id', $filters->categoryId);
        }

        $enrolledCourseIds = $query->pluck('id');

        if ($enrolledCourseIds->isEmpty()) {
            return collect();
        }

        return \App\Models\Instructor::query()
            ->whereHas('courses', function (Builder $query) use ($enrolledCourseIds): void {
                $query->whereIn('courses.id', $enrolledCourseIds);
            })
            ->with([
                'courses' => function ($query) use ($enrolledCourseIds): void {
                    $query->whereIn('courses.id', $enrolledCourseIds)
                        ->with(['center', 'category', 'instructors']);
                },
            ])
            ->get();
    }

    /**
     * @return Builder<Course>
     */
    private function mobileBaseQuery(User $student): Builder
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

        return $query;
    }
}
