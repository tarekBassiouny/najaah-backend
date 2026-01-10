<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Course;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Courses\CreateCourseRequest;
use App\Http\Requests\Admin\Courses\ListCoursesRequest;
use App\Http\Requests\Admin\Courses\UpdateCourseRequest;
use App\Http\Resources\Admin\Courses\CourseResource;
use App\Http\Resources\Admin\Courses\CourseSummaryResource;
use App\Models\Center;
use App\Models\Course;
use App\Models\User;
use App\Services\Centers\CenterScopeService;
use App\Services\Courses\Contracts\CourseServiceInterface;
use App\Services\Courses\CourseQueryService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class CourseController extends Controller
{
    public function __construct(
        private readonly CenterScopeService $centerScopeService
    ) {}

    public function adminList(
        ListCoursesRequest $request,
        CourseQueryService $queryService
    ): JsonResponse {
        $admin = $this->requireAdmin();
        $perPage = (int) $request->integer('per_page', 15);
        /** @var array<string, mixed> $filters */
        $filters = $request->validated();
        $paginator = $queryService->build($admin, $filters)->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Courses retrieved successfully',
            'data' => CourseSummaryResource::collection($paginator->items()),
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function centerIndex(
        ListCoursesRequest $request,
        Center $center
    ): JsonResponse {
        $admin = $this->requireAdmin();
        $this->centerScopeService->assertAdminCenterId($admin, (int) $center->id);

        $perPage = (int) $request->integer('per_page', 15);
        /** @var array<string, mixed> $filters */
        $filters = $request->validated();
        $centerId = (int) $center->id;

        $query = Course::query()
            ->with(['center', 'category', 'primaryInstructor', 'instructors'])
            ->orderByDesc('created_at')
            ->where('center_id', $centerId);

        if (isset($filters['category_id']) && is_numeric($filters['category_id'])) {
            $query->where('category_id', (int) $filters['category_id']);
        }

        if (isset($filters['primary_instructor_id']) && is_numeric($filters['primary_instructor_id'])) {
            $query->where('primary_instructor_id', (int) $filters['primary_instructor_id']);
        }

        if (isset($filters['search']) && is_string($filters['search'])) {
            $term = trim($filters['search']);
            if ($term !== '') {
                $query->where('title_translations', 'like', '%'.$term.'%');
            }
        }

        $paginator = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Courses retrieved successfully',
            'data' => CourseSummaryResource::collection($paginator->items()),
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function centerStore(
        CreateCourseRequest $request,
        Center $center,
        CourseServiceInterface $courseService
    ): JsonResponse {
        $admin = $this->requireAdmin();
        $this->centerScopeService->assertAdminCenterId($admin, (int) $center->id);

        /** @var array<string, mixed> $data */
        $data = $request->validated();
        $data['center_id'] = (int) $center->id;

        $course = $courseService->create($data, $admin);

        return response()->json([
            'success' => true,
            'message' => 'Course created successfully',
            'data' => new CourseResource($course),
        ], 201);
    }

    public function centerShow(
        Center $center,
        Course $course,
        CourseServiceInterface $courseService
    ): JsonResponse {
        $admin = $this->requireAdmin();
        $this->centerScopeService->assertAdminCenterId($admin, (int) $center->id);
        $this->assertCourseBelongsToCenter($center, $course);

        $course = $courseService->find((int) $course->id, $admin);

        return response()->json([
            'success' => true,
            'message' => 'Course retrieved successfully',
            'data' => $course !== null ? new CourseResource($course) : null,
        ]);
    }

    public function centerUpdate(
        UpdateCourseRequest $request,
        Center $center,
        Course $course,
        CourseServiceInterface $courseService
    ): JsonResponse {
        $admin = $this->requireAdmin();
        $this->centerScopeService->assertAdminCenterId($admin, (int) $center->id);
        $this->assertCourseBelongsToCenter($center, $course);

        /** @var array<string, mixed> $data */
        $data = $request->validated();
        unset($data['center_id']);

        $updated = $courseService->update($course, $data, $admin);

        return response()->json([
            'success' => true,
            'message' => 'Course updated successfully',
            'data' => new CourseResource($updated),
        ]);
    }

    public function centerDestroy(
        Center $center,
        Course $course,
        CourseServiceInterface $courseService
    ): JsonResponse {
        $admin = $this->requireAdmin();
        $this->centerScopeService->assertAdminCenterId($admin, (int) $center->id);
        $this->assertCourseBelongsToCenter($center, $course);

        $courseService->delete($course, $admin);

        return response()->json([
            'success' => true,
            'message' => 'Course deleted successfully',
            'data' => null,
        ], 204);
    }

    private function assertCourseBelongsToCenter(Center $center, Course $course): void
    {
        if ((int) $course->center_id !== (int) $center->id) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Course not found.',
                ],
            ], 404));
        }
    }

    private function requireAdmin(): User
    {
        $admin = request()->user();

        if (! $admin instanceof User) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Authentication required.',
                ],
            ], 401));
        }

        return $admin;
    }
}
