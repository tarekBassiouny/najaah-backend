<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Users\StoreAdminUserRequest;
use App\Http\Requests\Admin\Users\SyncAdminUserRolesRequest;
use App\Http\Requests\Admin\Users\UpdateAdminUserRequest;
use App\Http\Resources\Admin\Users\AdminUserResource;
use App\Models\User;
use App\Services\AdminUsers\AdminUserService;
use Illuminate\Http\JsonResponse;

class AdminUserController extends Controller
{
    public function __construct(
        private readonly AdminUserService $adminUserService
    ) {}

    /**
     * @queryParam per_page int Items per page. Example: 15
     */
    public function index(): JsonResponse
    {
        $perPage = (int) request()->query('per_page', 15);
        $paginator = $this->adminUserService->list($perPage);

        return response()->json([
            'success' => true,
            'data' => AdminUserResource::collection($paginator->items()),
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function store(StoreAdminUserRequest $request): JsonResponse
    {
        /** @var array<string, mixed> $data */
        $data = $request->validated();
        $admin = $this->adminUserService->create($data);

        return response()->json([
            'success' => true,
            'data' => new AdminUserResource($admin),
        ], 201);
    }

    public function update(UpdateAdminUserRequest $request, User $user): JsonResponse
    {
        /** @var array<string, mixed> $data */
        $data = $request->validated();
        $admin = $this->adminUserService->update($user, $data);

        return response()->json([
            'success' => true,
            'data' => new AdminUserResource($admin),
        ]);
    }

    public function destroy(User $user): JsonResponse
    {
        $this->adminUserService->delete($user);

        return response()->json([
            'success' => true,
            'data' => null,
        ], 204);
    }

    public function syncRoles(SyncAdminUserRolesRequest $request, User $user): JsonResponse
    {
        /** @var array{role_ids: array<int, int>} $data */
        $data = $request->validated();
        $admin = $this->adminUserService->syncRoles($user, $data['role_ids']);

        return response()->json([
            'success' => true,
            'data' => new AdminUserResource($admin),
        ]);
    }
}
