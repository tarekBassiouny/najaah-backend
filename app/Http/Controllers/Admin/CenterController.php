<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ListCentersRequest;
use App\Http\Requests\Centers\StoreCenterRequest;
use App\Http\Requests\Centers\UpdateCenterRequest;
use App\Http\Resources\Admin\AdminUserResource;
use App\Http\Resources\CenterResource;
use App\Models\Center;
use App\Models\User;
use App\Services\Admin\CenterQueryService;
use App\Services\Centers\CenterOnboardingService;
use App\Services\Centers\Contracts\CenterServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class CenterController extends Controller
{
    public function __construct(
        private readonly CenterServiceInterface $centerService,
        private readonly CenterOnboardingService $onboardingService,
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

    public function store(StoreCenterRequest $request): JsonResponse
    {
        /** @var array<string, mixed> $data */
        $data = $request->validated();
        $centerData = $this->normalizeCenterData($data);

        $owner = null;
        if (isset($data['owner_user_id']) && is_numeric($data['owner_user_id'])) {
            $owner = User::find((int) $data['owner_user_id']);
        }

        $ownerPayload = isset($data['owner']) && is_array($data['owner']) ? $data['owner'] : null;
        $roleSlug = isset($data['owner_role']) && is_string($data['owner_role']) ? $data['owner_role'] : 'center_owner';

        $result = $this->onboardingService->onboard($centerData, $owner, $ownerPayload, $roleSlug);

        return response()->json([
            'success' => true,
            'data' => [
                'center' => new CenterResource($result['center']),
                'owner' => new AdminUserResource($result['owner']),
                'email_sent' => $result['email_sent'],
            ],
        ], 201);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeCenterData(array $data): array
    {
        if (! isset($data['name_translations']) && isset($data['name']) && is_string($data['name'])) {
            $data['name_translations'] = ['en' => $data['name']];
        }

        if (! isset($data['slug']) && isset($data['name']) && is_string($data['name'])) {
            $base = Str::slug($data['name']);
            $slug = $base !== '' ? $base : Str::random(8);
            $candidate = $slug;
            $counter = 1;

            while (Center::where('slug', $candidate)->exists()) {
                $candidate = $slug.'-'.$counter;
                $counter++;
            }

            $data['slug'] = $candidate;
        }

        if (! isset($data['type'])) {
            $data['type'] = 0;
        }

        return $data;
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
