<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Users\ListAdminUsersRequest;
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
     * List admin users.
     *
     * @queryParam per_page int Items per page. Example: 15
     */
    public function index(ListAdminUsersRequest $request): JsonResponse
    {
        $filters = $request->filters();
        $paginator = $this->adminUserService->list($filters);

        return response()->json([
            'success' => true,
            'data' => AdminUserResource::collection($paginator->items()),
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    /**
     * Create an admin user.
     */
    public function store(StoreAdminUserRequest $request): JsonResponse
    {
        /** @var array<string, mixed> $data */
        $data = $request->validated();
        $actor = $request->user();
        $admin = $this->adminUserService->create($data, $actor instanceof User ? $actor : null);

        return response()->json([
            'success' => true,
            'data' => new AdminUserResource($admin),
        ], 201);
    }

    /**
     * Update an admin user.
     */
    public function update(UpdateAdminUserRequest $request, User $user): JsonResponse
    {
        /** @var array<string, mixed> $data */
        $data = $request->validated();
        $actor = $request->user();
        $admin = $this->adminUserService->update($user, $data, $actor instanceof User ? $actor : null);

        return response()->json([
            'success' => true,
            'data' => new AdminUserResource($admin),
        ]);
    }

    /**
     * Delete an admin user.
     */
    public function destroy(User $user): JsonResponse
    {
        $actor = request()->user();
        $this->adminUserService->delete($user, $actor instanceof User ? $actor : null);

        return response()->json([
            'success' => true,
            'data' => null,
        ], 204);
    }

    /**
     * Sync admin user roles.
     */
    public function syncRoles(SyncAdminUserRolesRequest $request, User $user): JsonResponse
    {
        /** @var array{role_ids: array<int, int>} $data */
        $data = $request->validated();
        $actor = $request->user();
        $admin = $this->adminUserService->syncRoles(
            $user,
            $data['role_ids'],
            $actor instanceof User ? $actor : null
        );

        return response()->json([
            'success' => true,
            'data' => new AdminUserResource($admin),
        ]);
    }
}
