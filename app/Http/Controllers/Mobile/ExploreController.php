<?php

declare(strict_types=1);

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mobile\ExploreCoursesRequest;
use App\Http\Requests\Mobile\ShowCourseRequest;
use App\Http\Resources\Mobile\CourseDetailsResource;
use App\Http\Resources\Mobile\ExploreCourseResource;
use App\Models\Center;
use App\Models\Course;
use App\Models\User;
use App\Services\Courses\ExploreCourseService;
use Illuminate\Http\JsonResponse;

class ExploreController extends Controller
{
    public function __construct(private readonly ExploreCourseService $exploreService) {}

    public function explore(ExploreCoursesRequest $request): JsonResponse
    {
        $student = $request->user();

        if (! $student instanceof User || $student->is_student === false) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Only students can access courses.',
                ],
            ], 403);
        }

        $filters = $request->filters();
        $paginator = $this->exploreService->explore($student, $filters);

        return response()->json([
            'success' => true,
            'data' => ExploreCourseResource::collection(collect($paginator->items())),
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function show(ShowCourseRequest $request, Center $center, Course $course): JsonResponse
    {
        $student = $request->user();

        if (! $student instanceof User || $student->is_student === false) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Only students can access courses.',
                ],
            ], 403);
        }

        if ((int) $course->center_id !== (int) $center->id) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Course not found.',
                ],
            ], 404);
        }

        $course = $this->exploreService->show($student, $course);

        return response()->json([
            'success' => true,
            'data' => new CourseDetailsResource($course),
        ]);
    }
}
