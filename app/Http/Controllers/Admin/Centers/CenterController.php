<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Centers;

use App\Actions\Admin\Centers\CreateCenterAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Centers\ListCentersRequest;
use App\Http\Requests\Admin\Centers\StoreCenterRequest;
use App\Http\Requests\Admin\Centers\UpdateCenterRequest;
use App\Http\Resources\Admin\Centers\CenterResource;
use App\Http\Resources\Admin\Users\AdminUserResource;
use App\Models\Center;
use App\Services\Admin\CenterQueryService;
use App\Services\Centers\Contracts\CenterServiceInterface;
use Illuminate\Http\JsonResponse;

class CenterController extends Controller
{
    public function __construct(
        private readonly CenterServiceInterface $centerService,
        private readonly CenterQueryService $queryService
    ) {}

    public function index(ListCentersRequest $request): JsonResponse
    {
        $perPage = (int) $request->integer('per_page', 15);
        /** @var array<string, mixed> $filters */
        $filters = $request->validated();

        $paginator = $this->queryService->build($filters)->paginate($perPage);

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

    public function store(StoreCenterRequest $request, CreateCenterAction $action): JsonResponse
    {
        /** @var array<string, mixed> $data */
        $data = $request->validated();
        $result = $action->execute($data);
        $centerData = (new CenterResource($result['center']))->toArray($request);
        $centerData['api_key'] = $result['center']->api_key;

        return response()->json([
            'success' => true,
            'data' => [
                'center' => $centerData,
                'owner' => new AdminUserResource($result['owner']),
                'email_sent' => $result['email_sent'],
            ],
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
