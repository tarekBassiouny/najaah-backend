<?php

declare(strict_types=1);

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mobile\ListCentersRequest;
use App\Http\Requests\Mobile\ShowCenterRequest;
use App\Http\Resources\Mobile\CenterResource;
use App\Http\Resources\Mobile\ExploreCourseResource;
use App\Models\Center;
use App\Models\User;
use App\Services\Centers\CenterService;
use Illuminate\Http\JsonResponse;

class CentersController extends Controller
{
    public function __construct(private readonly CenterService $centerService) {}

    public function index(ListCentersRequest $request): JsonResponse
    {
        /** @var User|null $student */
        $student = $request->user();

        // For authenticated users, verify they are students
        if ($student instanceof User && $student->is_student === false) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Only students can access centers.',
                ],
            ], 403);
        }

        // Branded students cannot list centers - they should only access their branded center
        if ($student instanceof User && is_numeric($student->center_id)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Branded students cannot list centers.',
                ],
            ], 403);
        }

        $filters = $request->filters();
        $paginator = $this->centerService->listUnbranded($student, $filters);

        return response()->json([
            'success' => true,
            'data' => CenterResource::collection(collect($paginator->items())),
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function show(ShowCenterRequest $request, Center $center): JsonResponse
    {
        /** @var User|null $student */
        $student = $request->user();

        // For authenticated users, verify they are students
        if ($student instanceof User && $student->is_student === false) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Only students can access centers.',
                ],
            ], 403);
        }

        $result = $this->centerService->showWithCourses(
            $student,
            $center,
            $request->perPage(),
            $request->categoryId(),
            $request->isFeatured()
        );
        $courses = $result['courses'];

        return response()->json([
            'success' => true,
            'data' => [
                'center' => new CenterResource($result['center']),
                'courses' => ExploreCourseResource::collection(collect($courses->items())),
            ],
            'meta' => [
                'page' => $courses->currentPage(),
                'per_page' => $courses->perPage(),
                'total' => $courses->total(),
            ],
        ]);
    }
}
