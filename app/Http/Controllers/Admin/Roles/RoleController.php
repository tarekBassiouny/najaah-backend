<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Roles;

use App\Actions\Roles\CreateRoleAction;
use App\Actions\Roles\DeleteRoleAction;
use App\Actions\Roles\ListRolesAction;
use App\Actions\Roles\SyncRolePermissionsAction;
use App\Actions\Roles\UpdateRoleAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Roles\StoreRoleRequest;
use App\Http\Requests\Admin\Roles\SyncRolePermissionsRequest;
use App\Http\Requests\Admin\Roles\UpdateRoleRequest;
use App\Http\Resources\Admin\Roles\RoleResource;
use App\Models\Role;
use Illuminate\Http\JsonResponse;

class RoleController extends Controller
{
    /**
     * @queryParam per_page int Items per page. Example: 15
     */
    public function index(ListRolesAction $action): JsonResponse
    {
        $perPage = (int) request()->query('per_page', 15);
        $paginator = $action->execute($perPage);

        return response()->json([
            'success' => true,
            'data' => RoleResource::collection($paginator->items()),
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function store(StoreRoleRequest $request, CreateRoleAction $action): JsonResponse
    {
        /** @var array<string, mixed> $data */
        $data = $request->validated();
        $role = $action->execute($data);

        return response()->json([
            'success' => true,
            'data' => new RoleResource($role),
        ], 201);
    }

    public function update(UpdateRoleRequest $request, Role $role, UpdateRoleAction $action): JsonResponse
    {
        /** @var array<string, mixed> $data */
        $data = $request->validated();
        $role = $action->execute($role, $data);

        return response()->json([
            'success' => true,
            'data' => new RoleResource($role),
        ]);
    }

    public function destroy(Role $role, DeleteRoleAction $action): JsonResponse
    {
        $action->execute($role);

        return response()->json([
            'success' => true,
            'data' => null,
        ], 204);
    }

    public function syncPermissions(
        SyncRolePermissionsRequest $request,
        Role $role,
        SyncRolePermissionsAction $action
    ): JsonResponse {
        /** @var array{permission_ids: array<int, int>} $data */
        $data = $request->validated();
        $role = $action->execute($role, $data['permission_ids']);

        return response()->json([
            'success' => true,
            'data' => new RoleResource($role),
        ]);
    }
}
