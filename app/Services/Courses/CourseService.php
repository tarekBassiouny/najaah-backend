<?php

declare(strict_types=1);

namespace App\Services\Courses;

use App\Enums\CourseStatus;
use App\Enums\VideoLifecycleStatus;
use App\Enums\VideoUploadStatus;
use App\Filters\Mobile\CourseFilters;
use App\Models\Course;
use App\Models\Instructor;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use App\Services\Centers\CenterScopeService;
use App\Services\Courses\Contracts\CourseServiceInterface;
use App\Support\AuditActions;
use App\Support\Guards\RejectNonScalarInput;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CourseService implements CourseServiceInterface
{
    public function __construct(
        private readonly CenterScopeService $centerScopeService,
        private readonly AuditLogService $auditLogService
    ) {}

    /** @return LengthAwarePaginator<Course> */
    public function paginate(int $perPage = 15, ?User $actor = null): LengthAwarePaginator
    {
        $query = Course::query()
            ->with(['center', 'category', 'primaryInstructor', 'instructors'])
            ->orderByDesc('id');

        if ($actor instanceof User && ! $this->centerScopeService->isSystemSuperAdmin($actor)) {
            $centerId = $this->centerScopeService->resolveAdminCenterId($actor);
            $this->centerScopeService->assertAdminCenterId($actor, $centerId);
            $query->where('center_id', $centerId);
        }

        return $query->paginate($perPage);
    }

    /** @param array<string, mixed> $data */
    public function create(array $data, ?User $actor = null): Course
    {
        RejectNonScalarInput::validate($data, ['title', 'description']);
        // Support legacy 'title'/'description' fields by mapping to '_translations'
        if (array_key_exists('title', $data) && ! array_key_exists('title_translations', $data)) {
            $data['title_translations'] = $data['title'];
        }

        if (array_key_exists('description', $data) && ! array_key_exists('description_translations', $data)) {
            $data['description_translations'] = $data['description'];
        }

        unset($data['title'], $data['description']);

        if (! array_key_exists('difficulty_level', $data) || ! is_numeric($data['difficulty_level'])) {
            $data['difficulty_level'] = 0;
        }

        if (! array_key_exists('language', $data) || ! is_string($data['language']) || $data['language'] === '') {
            $data['language'] = 'en';
        }

        $data['status'] = CourseStatus::Draft;
        $data['is_published'] = false;
        $data['publish_at'] = null;

        if ($actor instanceof User) {
            $centerId = isset($data['center_id']) && is_numeric($data['center_id']) ? (int) $data['center_id'] : null;
            $this->centerScopeService->assertAdminCenterId($actor, $centerId);
        }

        $course = Course::create($data);

        $this->auditLogService->log($actor, $course, AuditActions::COURSE_CREATED, [
            'center_id' => $course->center_id,
        ]);

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

        $this->auditLogService->log($actor, $course, AuditActions::COURSE_UPDATED, [
            'updated_fields' => array_keys($data),
        ]);

        return $course->fresh(['center', 'category', 'primaryInstructor', 'instructors']) ?? $course;
    }

    public function delete(Course $course, ?User $actor = null): void
    {
        if ($actor instanceof User) {
            $this->centerScopeService->assertAdminSameCenter($actor, $course);
        }

        $course->delete();

        $this->auditLogService->log($actor, $course, AuditActions::COURSE_DELETED);
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
    public function enrolled(User $student, CourseFilters $filters): LengthAwarePaginator
    {
        $builder = $this->mobileBaseQuery($student)
            ->enrolledBy($student);

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
     * @return Collection<int, Instructor>
     */
    public function enrolledGroupedByInstructor(User $student, CourseFilters $filters): Collection
    {
        $query = Course::query()
            ->published()
            ->enrolledBy($student)
            ->visibleToStudent($student);

        if ($filters->categoryId !== null) {
            $query->where('category_id', $filters->categoryId);
        }

        $enrolledCourseIds = $query->pluck('id');

        if ($enrolledCourseIds->isEmpty()) {
            return collect();
        }

        return Instructor::query()
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
            ->published()
            ->with(['center', 'category', 'instructors'])
            ->withEnrollmentMeta($student)
            ->visibleToStudent($student);

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

        return $query;
    }
}
