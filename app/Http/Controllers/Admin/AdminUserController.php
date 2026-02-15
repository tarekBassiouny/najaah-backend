<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Filters\Admin\AdminUserFilters;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Users\ListAdminUsersRequest;
use App\Http\Requests\Admin\Users\StoreAdminUserRequest;
use App\Http\Requests\Admin\Users\SyncAdminUserRolesRequest;
use App\Http\Requests\Admin\Users\UpdateAdminUserRequest;
use App\Http\Resources\Admin\Users\AdminUserResource;
use App\Models\Center;
use App\Models\User;
use App\Services\AdminUsers\AdminUserService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
    public function systemIndex(ListAdminUsersRequest $request): JsonResponse
    {
        $filters = $request->filters();
        $actor = $this->requireAdmin($request);
        $paginator = $this->adminUserService->list($filters, $actor);

        return $this->listResponse($paginator->items(), $paginator->currentPage(), $paginator->perPage(), $paginator->total(), $paginator->lastPage());
    }

    /**
     * List admin users for a center.
     */
    public function centerIndex(ListAdminUsersRequest $request, Center $center): JsonResponse
    {
        $actor = $this->requireAdmin($request);
        $requestFilters = $request->filters();
        $filters = new AdminUserFilters(
            page: $requestFilters->page,
            perPage: $requestFilters->perPage,
            centerId: (int) $center->id
        );

        $paginator = $this->adminUserService->list($filters, $actor);

        return $this->listResponse($paginator->items(), $paginator->currentPage(), $paginator->perPage(), $paginator->total(), $paginator->lastPage());
    }

    /**
     * Create an admin user in system scope.
     */
    public function systemStore(StoreAdminUserRequest $request): JsonResponse
    {
        /** @var array<string, mixed> $data */
        $data = $request->validated();
        $actor = $this->requireAdmin($request);
        $admin = $this->adminUserService->create($data, $actor);

        return response()->json([
            'success' => true,
            'data' => new AdminUserResource($admin),
        ], 201);
    }

    /**
     * Create an admin user in center scope.
     */
    public function centerStore(StoreAdminUserRequest $request, Center $center): JsonResponse
    {
        /** @var array<string, mixed> $data */
        $data = $request->validated();
        $actor = $this->requireAdmin($request);
        $admin = $this->adminUserService->create($data, $actor, (int) $center->id);

        return response()->json([
            'success' => true,
            'data' => new AdminUserResource($admin),
        ], 201);
    }

    /**
     * Update an admin user in system scope.
     */
    public function systemUpdate(UpdateAdminUserRequest $request, User $user): JsonResponse
    {
        /** @var array<string, mixed> $data */
        $data = $request->validated();
        $actor = $this->requireAdmin($request);
        $admin = $this->adminUserService->update($user, $data, $actor);

        return response()->json([
            'success' => true,
            'data' => new AdminUserResource($admin),
        ]);
    }

    /**
     * Update an admin user in center scope.
     */
    public function centerUpdate(UpdateAdminUserRequest $request, Center $center, User $user): JsonResponse
    {
        $this->assertAdminBelongsToCenter($center, $user);
        /** @var array<string, mixed> $data */
        $data = $request->validated();
        $actor = $this->requireAdmin($request);
        $admin = $this->adminUserService->update($user, $data, $actor, (int) $center->id);

        return response()->json([
            'success' => true,
            'data' => new AdminUserResource($admin),
        ]);
    }

    /**
     * Delete an admin user in system scope.
     */
    public function systemDestroy(Request $request, User $user): JsonResponse
    {
        $actor = $this->requireAdmin($request);
        $this->adminUserService->delete($user, $actor);

        return response()->json([
            'success' => true,
            'data' => null,
        ], 204);
    }

    /**
     * Delete an admin user in center scope.
     */
    public function centerDestroy(Request $request, Center $center, User $user): JsonResponse
    {
        $this->assertAdminBelongsToCenter($center, $user);
        $actor = $this->requireAdmin($request);
        $this->adminUserService->delete($user, $actor, (int) $center->id);

        return response()->json([
            'success' => true,
            'data' => null,
        ], 204);
    }

    /**
     * Sync admin user roles in system scope.
     */
    public function systemSyncRoles(SyncAdminUserRolesRequest $request, User $user): JsonResponse
    {
        /** @var array{role_ids: array<int, int>} $data */
        $data = $request->validated();
        $actor = $this->requireAdmin($request);
        $admin = $this->adminUserService->syncRoles(
            $user,
            $data['role_ids'],
            $actor
        );

        return response()->json([
            'success' => true,
            'data' => new AdminUserResource($admin),
        ]);
    }

    /**
     * Sync admin user roles in center scope.
     */
    public function centerSyncRoles(
        SyncAdminUserRolesRequest $request,
        Center $center,
        User $user
    ): JsonResponse {
        $this->assertAdminBelongsToCenter($center, $user);

        /** @var array{role_ids: array<int, int>} $data */
        $data = $request->validated();
        $actor = $this->requireAdmin($request);
        $admin = $this->adminUserService->syncRoles(
            $user,
            $data['role_ids'],
            $actor,
            (int) $center->id
        );

        return response()->json([
            'success' => true,
            'data' => new AdminUserResource($admin),
        ]);
    }

    private function requireAdmin(Request $request): User
    {
        $actor = $request->user();

        if (! $actor instanceof User) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Authentication required.',
                ],
            ], 401));
        }

        return $actor;
    }

    private function assertAdminBelongsToCenter(Center $center, User $user): void
    {
        if ($user->is_student || ! is_numeric($user->center_id) || (int) $user->center_id !== (int) $center->id) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Admin user not found.',
                ],
            ], 404));
        }
    }

    /**
     * @param  array<int, User>  $items
     */
    private function listResponse(array $items, int $page, int $perPage, int $total, int $lastPage): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => AdminUserResource::collection($items),
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => $lastPage,
            ],
        ]);
    }
}
