<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Roles;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Roles\BulkAssignRolePermissionsRequest;
use App\Http\Requests\Admin\Roles\ListRolesRequest;
use App\Http\Requests\Admin\Roles\StoreRoleRequest;
use App\Http\Requests\Admin\Roles\SyncRolePermissionsRequest;
use App\Http\Requests\Admin\Roles\UpdateRoleRequest;
use App\Http\Resources\Admin\Roles\RoleResource;
use App\Models\Role;
use App\Models\User;
use App\Services\Roles\Contracts\RoleServiceInterface;
use Illuminate\Http\JsonResponse;

class RoleController extends Controller
{
    public function __construct(
        private readonly RoleServiceInterface $roleService
    ) {}

    /**
     * List roles.
     *
     * @queryParam per_page int Items per page. Example: 15
     */
    public function index(ListRolesRequest $request): JsonResponse
    {
        $filters = $request->filters();
        $paginator = $this->roleService->list($filters);

        return response()->json([
            'success' => true,
            'data' => RoleResource::collection($paginator->items()),
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    /**
     * Show a role.
     */
    public function show(Role $role): JsonResponse
    {
        $role->load('permissions');

        return response()->json([
            'success' => true,
            'data' => new RoleResource($role),
        ]);
    }

    /**
     * Create a role.
     */
    public function store(StoreRoleRequest $request): JsonResponse
    {
        $admin = $request->user();
        /** @var array<string, mixed> $data */
        $data = $request->validated();
        $role = $this->roleService->create($data, $admin instanceof User ? $admin : null);

        return response()->json([
            'success' => true,
            'data' => new RoleResource($role),
        ], 201);
    }

    /**
     * Update a role.
     */
    public function update(UpdateRoleRequest $request, Role $role): JsonResponse
    {
        $admin = $request->user();
        /** @var array<string, mixed> $data */
        $data = $request->validated();
        $role = $this->roleService->update($role, $data, $admin instanceof User ? $admin : null);

        return response()->json([
            'success' => true,
            'data' => new RoleResource($role),
        ]);
    }

    /**
     * Delete a role.
     */
    public function destroy(Role $role): JsonResponse
    {
        $admin = request()->user();
        $this->roleService->delete($role, $admin instanceof User ? $admin : null);

        return response()->json([
            'success' => true,
            'data' => null,
        ], 204);
    }

    /**
     * Sync role permissions.
     */
    public function syncPermissions(SyncRolePermissionsRequest $request, Role $role): JsonResponse
    {
        $admin = $request->user();
        /** @var array{permission_ids: array<int, int>} $data */
        $data = $request->validated();
        $role = $this->roleService->syncPermissions(
            $role,
            $data['permission_ids'],
            $admin instanceof User ? $admin : null
        );

        return response()->json([
            'success' => true,
            'data' => new RoleResource($role),
        ]);
    }

    public function bulkSyncPermissions(BulkAssignRolePermissionsRequest $request): JsonResponse
    {
        $admin = $request->user();
        /** @var array{role_ids: array<int, int>, permission_ids: array<int, int>} $data */
        $data = $request->validated();
        $summary = $this->roleService->bulkSyncPermissions(
            $data['role_ids'],
            $data['permission_ids'],
            $admin instanceof User ? $admin : null
        );

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }
}
