<?php

declare(strict_types=1);

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mobile\ListCategoriesRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Center;
use App\Models\User;
use App\Services\Categories\MobileCategoryService;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function __construct(private readonly MobileCategoryService $service) {}

    public function index(ListCategoriesRequest $request): JsonResponse
    {
        /** @var User|null $student */
        $student = $request->user();

        // For authenticated users, verify they are students
        if ($student instanceof User && $student->is_student === false) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Only students can access categories.',
                ],
            ], 403);
        }

        $filters = $request->filters();
        $paginator = $this->service->list($student, $filters);

        return response()->json([
            'success' => true,
            'data' => CategoryResource::collection(collect($paginator->items())),
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function centerIndex(ListCategoriesRequest $request, Center $center): JsonResponse
    {
        /** @var User|null $student */
        $student = $request->user();

        // For authenticated users, verify they are students
        if ($student instanceof User && $student->is_student === false) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Only students can access categories.',
                ],
            ], 403);
        }

        $filters = $request->filters();
        $paginator = $this->service->listForCenter($center, $filters);

        return response()->json([
            'success' => true,
            'data' => CategoryResource::collection(collect($paginator->items())),
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }
}
