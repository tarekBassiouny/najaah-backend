<?php

declare(strict_types=1);

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mobile\EnrolledCoursesByInstructorRequest;
use App\Http\Requests\Mobile\EnrolledCoursesRequest;
use App\Http\Resources\Mobile\ExploreCourseResource;
use App\Http\Resources\Mobile\InstructorWithCoursesResource;
use App\Models\User;
use App\Services\Courses\CourseService;
use Illuminate\Http\JsonResponse;

class EnrolledCoursesController extends Controller
{
    public function __construct(private readonly CourseService $courseService) {}

    public function index(EnrolledCoursesRequest $request): JsonResponse
    {
        $student = $request->user();

        if (! $student instanceof User || $student->is_student === false) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Only students can access enrolled courses.',
                ],
            ], 403);
        }

        $filters = $request->filters();
        $paginator = $this->courseService->enrolled($student, $filters);

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

    public function byInstructor(EnrolledCoursesByInstructorRequest $request): JsonResponse
    {
        $student = $request->user();

        if (! $student instanceof User || $student->is_student === false) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Only students can access enrolled courses.',
                ],
            ], 403);
        }

        $filters = $request->filters();
        $instructors = $this->courseService->enrolledGroupedByInstructor($student, $filters);

        return response()->json([
            'success' => true,
            'data' => InstructorWithCoursesResource::collection($instructors),
        ]);
    }
}
