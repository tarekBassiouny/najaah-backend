<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Centers\StoreCenterRequest;
use App\Http\Requests\Centers\UpdateCenterRequest;
use App\Http\Resources\CenterResource;
use App\Models\Center;
use App\Services\Centers\Contracts\CenterServiceInterface;
use Illuminate\Http\JsonResponse;

class CenterController extends Controller
{
    public function __construct(
        private readonly CenterServiceInterface $centerService
    ) {}

    public function index(): JsonResponse
    {
        $perPage = (int) request()->query('per_page', 15);
        $filters = request()->only(['slug', 'type']);

        $paginator = $this->centerService->paginate($perPage, $filters);

        return response()->json([
            'success' => true,
            'message' => 'Centers retrieved successfully',
            'data' => CenterResource::collection($paginator->items()),
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function store(StoreCenterRequest $request): JsonResponse
    {
        /** @var array<string, mixed> $data */
        $data = $request->validated();
        $center = $this->centerService->create($data);

        return response()->json([
            'success' => true,
            'message' => 'Center created successfully',
            'data' => new CenterResource($center),
        ], 201);
    }

    public function show(int $center): JsonResponse
    {
        $center = Center::with('setting')->find($center);

        if ($center === null) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Center not found',
                ],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Center retrieved successfully',
            'data' => new CenterResource($center),
        ]);
    }

    public function update(UpdateCenterRequest $request, int $center): JsonResponse
    {
        $center = Center::find($center);

        if ($center === null) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Center not found',
                ],
            ], 404);
        }

        /** @var array<string, mixed> $data */
        $data = $request->validated();
        $updated = $this->centerService->update($center, $data);

        return response()->json([
            'success' => true,
            'message' => 'Center updated successfully',
            'data' => new CenterResource($updated),
        ]);
    }

    public function destroy(int $center): JsonResponse
    {
        $center = Center::find($center);

        if ($center === null) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Center not found',
                ],
            ], 404);
        }

        $this->centerService->delete($center);

        return response()->json([
            'success' => true,
            'message' => 'Center deleted successfully',
            'data' => null,
        ], 204);
    }

    public function restore(int $center): JsonResponse
    {
        $restored = $this->centerService->restore($center);

        if ($restored === null) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Center not found',
                ],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Center restored successfully',
            'data' => new CenterResource($restored),
        ]);
    }
}
