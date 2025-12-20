<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\AdminUsers\CreateAdminUserAction;
use App\Actions\AdminUsers\DeleteAdminUserAction;
use App\Actions\AdminUsers\ListAdminUsersAction;
use App\Actions\AdminUsers\SyncAdminUserRolesAction;
use App\Actions\AdminUsers\UpdateAdminUserAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdminUsers\StoreAdminUserRequest;
use App\Http\Requests\AdminUsers\SyncAdminUserRolesRequest;
use App\Http\Requests\AdminUsers\UpdateAdminUserRequest;
use App\Http\Resources\Admin\AdminUserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class AdminUserController extends Controller
{
    /**
     * @queryParam per_page int Items per page. Example: 15
     */
    public function index(ListAdminUsersAction $action): JsonResponse
    {
        $perPage = (int) request()->query('per_page', 15);
        $paginator = $action->execute($perPage);

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

    public function store(StoreAdminUserRequest $request, CreateAdminUserAction $action): JsonResponse
    {
        /** @var array<string, mixed> $data */
        $data = $request->validated();
        $admin = $action->execute($data);

        return response()->json([
            'success' => true,
            'data' => new AdminUserResource($admin),
        ], 201);
    }

    public function update(
        UpdateAdminUserRequest $request,
        User $user,
        UpdateAdminUserAction $action
    ): JsonResponse {
        /** @var array<string, mixed> $data */
        $data = $request->validated();
        $admin = $action->execute($user, $data);

        return response()->json([
            'success' => true,
            'data' => new AdminUserResource($admin),
        ]);
    }

    public function destroy(User $user, DeleteAdminUserAction $action): JsonResponse
    {
        $action->execute($user);

        return response()->json([
            'success' => true,
            'data' => null,
        ], 204);
    }

    public function syncRoles(
        SyncAdminUserRolesRequest $request,
        User $user,
        SyncAdminUserRolesAction $action
    ): JsonResponse {
        /** @var array{role_ids: array<int, int>} $data */
        $data = $request->validated();
        $admin = $action->execute($user, $data['role_ids']);

        return response()->json([
            'success' => true,
            'data' => new AdminUserResource($admin),
        ]);
    }
}
