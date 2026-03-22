<?php

declare(strict_types=1);

namespace App\Services\Courses;

use App\Enums\CourseAccessModel;
use App\Enums\CourseStatus;
use App\Enums\VideoLifecycleStatus;
use App\Enums\VideoUploadStatus;
use App\Exceptions\DomainException;
use App\Filters\Mobile\CourseFilters;
use App\Models\Course;
use App\Models\Instructor;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use App\Services\Centers\CenterScopeService;
use App\Services\Courses\Contracts\CourseServiceInterface;
use App\Services\Settings\PolicySettingsService;
use App\Support\AuditActions;
use App\Support\ErrorCodes;
use App\Support\Guards\RejectNonScalarInput;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CourseService implements CourseServiceInterface
{
    private readonly PolicySettingsService $policySettingsService;

    public function __construct(
        private readonly CenterScopeService $centerScopeService,
        private readonly AuditLogService $auditLogService,
        ?PolicySettingsService $policySettingsService = null
    ) {
        $this->policySettingsService = $policySettingsService ?? app(PolicySettingsService::class);
    }

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

        $showForAllStudents = $this->resolveShowForAllStudentsForCreate($data);
        $gradeIds = $this->extractTargetIds($data, 'grade_ids');
        $schoolIds = $this->extractTargetIds($data, 'school_ids');
        $collegeIds = $this->extractTargetIds($data, 'college_ids');
        unset($data['grade_ids'], $data['school_ids'], $data['college_ids']);

        $data['status'] = CourseStatus::Draft;
        $data['is_published'] = false;
        $data['publish_at'] = null;
        $data['show_for_all_students'] = $showForAllStudents;

        if ($actor instanceof User) {
            $centerId = isset($data['center_id']) && is_numeric($data['center_id']) ? (int) $data['center_id'] : null;
            $this->centerScopeService->assertAdminCenterId($actor, $centerId);
        }

        $course = Course::create($data);
        $this->syncEducationTargets($course, $showForAllStudents, $gradeIds, $schoolIds, $collegeIds, true);

        $this->auditLogService->log($actor, $course, AuditActions::COURSE_CREATED, [
            'center_id' => $course->center_id,
        ]);

        return $course->fresh(['center', 'category', 'primaryInstructor', 'instructors', 'grades', 'schools', 'colleges']) ?? $course;
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

        $this->assertAccessModelTransitionAllowed($course, $data);

        $explicitShowForAll = array_key_exists('show_for_all_students', $data) ? (bool) $data['show_for_all_students'] : null;
        $gradeIdsProvided = array_key_exists('grade_ids', $data) && is_array($data['grade_ids']);
        $schoolIdsProvided = array_key_exists('school_ids', $data) && is_array($data['school_ids']);
        $collegeIdsProvided = array_key_exists('college_ids', $data) && is_array($data['college_ids']);
        $gradeIds = $this->extractTargetIds($data, 'grade_ids');
        $schoolIds = $this->extractTargetIds($data, 'school_ids');
        $collegeIds = $this->extractTargetIds($data, 'college_ids');
        unset($data['grade_ids'], $data['school_ids'], $data['college_ids']);

        $course->update($data);
        $this->syncEducationTargets(
            $course,
            $explicitShowForAll,
            $gradeIds,
            $schoolIds,
            $collegeIds,
            false,
            $gradeIdsProvided,
            $schoolIdsProvided,
            $collegeIdsProvided
        );

        $this->auditLogService->log($actor, $course, AuditActions::COURSE_UPDATED, [
            'updated_fields' => array_keys($data),
        ]);

        return $course->fresh(['center', 'category', 'primaryInstructor', 'instructors', 'grades', 'schools', 'colleges']) ?? $course;
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
        $query = Course::with(['center', 'category', 'primaryInstructor', 'instructors', 'sections.videos', 'sections.pdfs', 'grades', 'schools', 'colleges']);

        $course = $query->find($id);

        if ($actor instanceof User && $course !== null) {
            $this->centerScopeService->assertAdminSameCenter($actor, $course);
        }

        return $course;
    }

    /**
     * @return LengthAwarePaginator<Course>
     */
    public function search(?User $student, ?string $query, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        $builder = $student instanceof User
            ? $this->mobileBaseQuery($student)
            : $this->guestBaseQuery();

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
    public function fallback(?User $student): Collection
    {
        // For guests, return recent courses from centers that allow guest browsing
        if (! $student instanceof User) {
            return $this->guestBaseQuery()
                ->orderByDesc('created_at')
                ->limit(5)
                ->get();
        }

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
     * Get courses the student has access to (via enrollment OR video code redemptions).
     *
     * @return LengthAwarePaginator<Course>
     */
    public function enrolled(User $student, CourseFilters $filters): LengthAwarePaginator
    {
        $builder = $this->mobileBaseQuery($student)
            ->accessibleBy($student);

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
     * Get courses the student has access to, grouped by instructor.
     *
     * Finds instructors linked via the course_instructors pivot OR via
     * primary_instructor_id, deduplicates them, and attaches only the
     * accessible courses (also deduplicated) to each instructor.
     *
     * @return Collection<int, Instructor>
     */
    public function enrolledGroupedByInstructor(User $student, CourseFilters $filters): Collection
    {
        $query = $this->mobileBaseQuery($student)
            ->accessibleBy($student);

        if ($filters->categoryId !== null) {
            $query->where('category_id', $filters->categoryId);
        }

        $accessibleCourses = $query
            ->with(['center', 'category', 'instructors'])
            ->get();

        if ($accessibleCourses->isEmpty()) {
            return collect();
        }

        $accessibleCourseIds = $accessibleCourses->pluck('id');

        // Instructors linked via pivot
        $pivotInstructors = Instructor::query()
            ->whereHas('courses', function (Builder $q) use ($accessibleCourseIds): void {
                $q->whereIn('courses.id', $accessibleCourseIds);
            })
            ->get();

        // Instructors linked only via primary_instructor_id
        $primaryInstructorIds = $accessibleCourses
            ->pluck('primary_instructor_id')
            ->filter()
            ->unique();

        $missingPrimaryIds = $primaryInstructorIds->diff($pivotInstructors->pluck('id'));

        $primaryOnlyInstructors = $missingPrimaryIds->isNotEmpty()
            ? Instructor::query()->whereIn('id', $missingPrimaryIds)->get()
            : collect();

        // Merge and deduplicate instructors
        $allInstructors = $pivotInstructors->merge($primaryOnlyInstructors)->unique('id');

        // Build a map of instructor ID → accessible courses (deduplicated)
        $instructorCoursesMap = [];
        foreach ($accessibleCourses as $course) {
            // Courses linked via pivot
            foreach ($course->instructors as $instructor) {
                $instructorCoursesMap[$instructor->id][$course->id] = $course;
            }

            // Courses linked via primary_instructor_id
            if ($course->primary_instructor_id !== null) {
                $instructorCoursesMap[$course->primary_instructor_id][$course->id] = $course;
            }
        }

        // Attach deduplicated courses to each instructor
        foreach ($allInstructors as $instructor) {
            $courses = collect($instructorCoursesMap[$instructor->id] ?? [])->values();
            $instructor->setRelation('courses', $courses);
        }

        return $allInstructors->values();
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
            ->visibleToStudent($student)
            ->matchingStudentEducation($student);

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

    /**
     * Base query for guest users - shows courses from centers that allow guest browsing.
     *
     * @return Builder<Course>
     */
    private function guestBaseQuery(): Builder
    {
        $policySettingsService = $this->policySettingsService;

        $query = Course::query()
            ->published()
            ->with(['center', 'category', 'instructors'])
            ->whereHas('center',
                /** @param Builder<\App\Models\Center> $query */
                function (Builder $query) use ($policySettingsService): void {
                    $query->where('status', \App\Models\Center::STATUS_ACTIVE->value);
                    $policySettingsService->applyGuestBrowsingFilter($query);
                }
            )
            ->where('show_for_all_students', true);

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

    /**
     * @param  array<string, mixed>  $data
     * @return array<int>
     */
    private function extractTargetIds(array $data, string $key): array
    {
        if (! array_key_exists($key, $data) || ! is_array($data[$key])) {
            return [];
        }

        return array_values(array_unique(array_map(
            static fn (mixed $value): int => (int) $value,
            array_filter($data[$key], static fn (mixed $value): bool => is_numeric($value))
        )));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveShowForAllStudentsForCreate(array $data): bool
    {
        if (array_key_exists('show_for_all_students', $data)) {
            return (bool) $data['show_for_all_students'];
        }

        $hasTargets = $this->extractTargetIds($data, 'grade_ids') !== []
            || $this->extractTargetIds($data, 'school_ids') !== []
            || $this->extractTargetIds($data, 'college_ids') !== [];

        return ! $hasTargets;
    }

    /**
     * @param  array<int>  $gradeIds
     * @param  array<int>  $schoolIds
     * @param  array<int>  $collegeIds
     */
    private function syncEducationTargets(
        Course $course,
        ?bool $showForAllStudents,
        array $gradeIds,
        array $schoolIds,
        array $collegeIds,
        bool $isCreate,
        bool $gradeIdsProvided = false,
        bool $schoolIdsProvided = false,
        bool $collegeIdsProvided = false
    ): void {
        if ($showForAllStudents === true) {
            $course->grades()->sync([]);
            $course->schools()->sync([]);
            $course->colleges()->sync([]);

            if (! $course->show_for_all_students) {
                $course->update(['show_for_all_students' => true]);
            }

            return;
        }

        if ($showForAllStudents === false && $course->show_for_all_students) {
            $course->update(['show_for_all_students' => false]);
        }

        if ($isCreate) {
            $course->grades()->sync($gradeIds);
            $course->schools()->sync($schoolIds);
            $course->colleges()->sync($collegeIds);

            return;
        }

        if ($gradeIdsProvided) {
            $course->grades()->sync($gradeIds);
        }

        if ($schoolIdsProvided) {
            $course->schools()->sync($schoolIds);
        }

        if ($collegeIdsProvided) {
            $course->colleges()->sync($collegeIds);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function assertAccessModelTransitionAllowed(Course $course, array $data): void
    {
        if (! array_key_exists('access_model', $data)) {
            return;
        }

        $targetAccessModel = $this->resolveAccessModel($data['access_model']);

        if (! $targetAccessModel instanceof CourseAccessModel || $targetAccessModel === $course->access_model) {
            return;
        }

        $this->deny(
            'Course access model cannot be changed after creation. Create a new course instead.',
            ErrorCodes::INVALID_STATE,
            422
        );
    }

    private function resolveAccessModel(mixed $value): ?CourseAccessModel
    {
        if ($value instanceof CourseAccessModel) {
            return $value;
        }

        if (! is_string($value)) {
            return null;
        }

        return CourseAccessModel::tryFrom($value);
    }

    /**
     * @return never
     */
    private function deny(string $message, string $code, int $status): void
    {
        throw new DomainException($message, $code, $status);
    }
}
