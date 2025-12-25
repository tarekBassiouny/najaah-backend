<?php

declare(strict_types=1);

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mobile\ListCentersRequest;
use App\Http\Requests\Mobile\ShowCenterRequest;
use App\Http\Resources\Mobile\CenterResource;
use App\Http\Resources\Mobile\ExploreCourseResource;
use App\Models\Center;
use App\Services\Centers\CenterService;
use Illuminate\Http\JsonResponse;

class CentersController extends Controller
{
    public function __construct(private readonly CenterService $centerService) {}

    public function index(ListCentersRequest $request): JsonResponse
    {
        $filters = $request->filters();
        $paginator = $this->centerService->listUnbranded($filters->search, $filters->perPage);

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
        $student = $request->user();

        $result = $this->centerService->showWithCourses($student, $center, $request->perPage());
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
