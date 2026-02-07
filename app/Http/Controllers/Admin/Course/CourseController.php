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

    /**
     * List courses for admins.
     */
    public function adminList(
        ListCoursesRequest $request,
        CourseQueryService $queryService
    ): JsonResponse {
        $admin = $this->requireAdmin();
        $filters = $request->filters();
        $paginator = $queryService->paginate($admin, $filters);

        return response()->json([
            'success' => true,
            'message' => 'Courses retrieved successfully',
            'data' => CourseSummaryResource::collection($paginator->items()),
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    /**
     * List courses for a center.
     */
    public function centerIndex(
        ListCoursesRequest $request,
        Center $center,
        CourseQueryService $queryService
    ): JsonResponse {
        $admin = $this->requireAdmin();
        $filters = $request->filters();
        $paginator = $queryService->paginateForCenter($admin, (int) $center->id, $filters);

        return response()->json([
            'success' => true,
            'message' => 'Courses retrieved successfully',
            'data' => CourseSummaryResource::collection($paginator->items()),
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    /**
     * Create a course for a center.
     */
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

    /**
     * Show a center course.
     */
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

    /**
     * Update a center course.
     */
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

    /**
     * Delete a center course.
     */
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
