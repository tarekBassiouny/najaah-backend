<?php

declare(strict_types=1);

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mobile\ListInstructorsRequest;
use App\Http\Resources\Mobile\InstructorResource;
use App\Models\User;
use App\Services\Instructors\MobileInstructorService;
use Illuminate\Http\JsonResponse;

class InstructorController extends Controller
{
    public function __construct(private readonly MobileInstructorService $service) {}

    public function index(ListInstructorsRequest $request): JsonResponse
    {
        $student = $request->user();

        if (! $student instanceof User || $student->is_student === false) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Only students can access instructors.',
                ],
            ], 403);
        }

        $filters = $request->filters();
        $paginator = $this->service->list($student, $filters);

        return response()->json([
            'success' => true,
            'data' => InstructorResource::collection(collect($paginator->items())),
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }
}
