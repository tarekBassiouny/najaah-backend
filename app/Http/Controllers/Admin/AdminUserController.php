<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Filters\Admin\AdminUserFilters;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Users\AssignAdminUserCenterRequest;
use App\Http\Requests\Admin\Users\BulkAssignAdminUsersToCentersRequest;
use App\Http\Requests\Admin\Users\BulkSyncAdminUserRolesRequest;
use App\Http\Requests\Admin\Users\BulkUpdateAdminUserStatusRequest;
use App\Http\Requests\Admin\Users\ListAdminUsersRequest;
use App\Http\Requests\Admin\Users\StoreAdminUserRequest;
use App\Http\Requests\Admin\Users\SyncAdminUserRolesRequest;
use App\Http\Requests\Admin\Users\UpdateAdminUserRequest;
use App\Http\Requests\Admin\Users\UpdateAdminUserStatusRequest;
use App\Http\Resources\Admin\Users\AdminUserResource;
use App\Models\Center;
use App\Models\User;
use App\Services\AdminUsers\Contracts\AdminUserServiceInterface;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    public function __construct(
        private readonly AdminUserServiceInterface $adminUserService
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
            centerId: (int) $center->id,
            search: $requestFilters->search,
            roleId: $requestFilters->roleId
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
     * Update an admin user status in system scope.
     */
    public function systemUpdateStatus(UpdateAdminUserStatusRequest $request, User $user): JsonResponse
    {
        /** @var array{status:int} $data */
        $data = $request->validated();
        $actor = $this->requireAdmin($request);
        $admin = $this->adminUserService->updateStatus(
            $user,
            (int) $data['status'],
            $actor
        );

        return response()->json([
            'success' => true,
            'data' => new AdminUserResource($admin),
        ]);
    }

    /**
     * Bulk update admin user statuses in system scope.
     */
    public function systemBulkUpdateStatus(BulkUpdateAdminUserStatusRequest $request): JsonResponse
    {
        /** @var array{status:int, user_ids:array<int, int>} $data */
        $data = $request->validated();
        $actor = $this->requireAdmin($request);
        $result = $this->adminUserService->bulkUpdateStatus(
            $data['user_ids'],
            (int) $data['status'],
            $actor
        );

        return response()->json([
            'success' => true,
            'message' => 'Bulk admin status update processed',
            'data' => [
                'counts' => [
                    'total' => count(array_values(array_unique(array_map('intval', $data['user_ids'])))),
                    'updated' => count($result['updated']),
                    'skipped' => count($result['skipped']),
                    'failed' => count($result['failed']),
                ],
                'updated' => AdminUserResource::collection($result['updated']),
                'skipped' => $result['skipped'],
                'failed' => $result['failed'],
            ],
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
     * Update an admin user status in center scope.
     */
    public function centerUpdateStatus(
        UpdateAdminUserStatusRequest $request,
        Center $center,
        User $user
    ): JsonResponse {
        $this->assertAdminBelongsToCenter($center, $user);
        /** @var array{status:int} $data */
        $data = $request->validated();
        $actor = $this->requireAdmin($request);
        $admin = $this->adminUserService->updateStatus(
            $user,
            (int) $data['status'],
            $actor,
            (int) $center->id
        );

        return response()->json([
            'success' => true,
            'data' => new AdminUserResource($admin),
        ]);
    }

    /**
     * Bulk update admin user statuses in center scope.
     */
    public function centerBulkUpdateStatus(
        BulkUpdateAdminUserStatusRequest $request,
        Center $center
    ): JsonResponse {
        /** @var array{status:int, user_ids:array<int, int>} $data */
        $data = $request->validated();
        $actor = $this->requireAdmin($request);
        $result = $this->adminUserService->bulkUpdateStatus(
            $data['user_ids'],
            (int) $data['status'],
            $actor,
            (int) $center->id
        );

        return response()->json([
            'success' => true,
            'message' => 'Bulk admin status update processed',
            'data' => [
                'counts' => [
                    'total' => count(array_values(array_unique(array_map('intval', $data['user_ids'])))),
                    'updated' => count($result['updated']),
                    'skipped' => count($result['skipped']),
                    'failed' => count($result['failed']),
                ],
                'updated' => AdminUserResource::collection($result['updated']),
                'skipped' => $result['skipped'],
                'failed' => $result['failed'],
            ],
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
     * Bulk sync admin user roles in system scope.
     */
    public function systemBulkSyncRoles(BulkSyncAdminUserRolesRequest $request): JsonResponse
    {
        /** @var array{user_ids: array<int, int>, role_ids: array<int, int>} $data */
        $data = $request->validated();
        $actor = $this->requireAdmin($request);
        $result = $this->adminUserService->bulkSyncRoles(
            $data['user_ids'],
            $data['role_ids'],
            $actor
        );

        return response()->json([
            'success' => true,
            'message' => 'Bulk admin role assignment processed',
            'data' => [
                'counts' => [
                    'total' => count(array_values(array_unique(array_map('intval', $data['user_ids'])))),
                    'updated' => count($result['updated']),
                    'skipped' => count($result['skipped']),
                    'failed' => count($result['failed']),
                ],
                'updated' => AdminUserResource::collection($result['updated']),
                'skipped' => $result['skipped'],
                'failed' => $result['failed'],
            ],
        ]);
    }

    /**
     * Assign admin user center in system scope.
     */
    public function systemAssignCenter(AssignAdminUserCenterRequest $request, User $user): JsonResponse
    {
        /** @var array{center_id:int} $data */
        $data = $request->validated();
        $actor = $this->requireAdmin($request);
        $admin = $this->adminUserService->assignCenter(
            $user,
            (int) $data['center_id'],
            $actor
        );

        return response()->json([
            'success' => true,
            'data' => new AdminUserResource($admin),
        ]);
    }

    /**
     * Bulk assign admin users to centers in system scope.
     */
    public function systemBulkAssignCenters(BulkAssignAdminUsersToCentersRequest $request): JsonResponse
    {
        /**
         * @var array{
         *   assignments: array<int, array{user_id:int, center_id:int}>
         * } $data
         */
        $data = $request->validated();
        $actor = $this->requireAdmin($request);
        $result = $this->adminUserService->bulkAssignCenters($data['assignments'], $actor);

        return response()->json([
            'success' => true,
            'message' => 'Bulk admin center assignment processed',
            'data' => [
                'counts' => [
                    'total' => count($data['assignments']),
                    'updated' => count($result['updated']),
                    'skipped' => count($result['skipped']),
                    'failed' => count($result['failed']),
                ],
                'updated' => AdminUserResource::collection($result['updated']),
                'skipped' => $result['skipped'],
                'failed' => $result['failed'],
            ],
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

    /**
     * Bulk sync admin user roles in center scope.
     */
    public function centerBulkSyncRoles(
        BulkSyncAdminUserRolesRequest $request,
        Center $center
    ): JsonResponse {
        /** @var array{user_ids: array<int, int>, role_ids: array<int, int>} $data */
        $data = $request->validated();
        $actor = $this->requireAdmin($request);
        $result = $this->adminUserService->bulkSyncRoles(
            $data['user_ids'],
            $data['role_ids'],
            $actor,
            (int) $center->id
        );

        return response()->json([
            'success' => true,
            'message' => 'Bulk admin role assignment processed',
            'data' => [
                'counts' => [
                    'total' => count(array_values(array_unique(array_map('intval', $data['user_ids'])))),
                    'updated' => count($result['updated']),
                    'skipped' => count($result['skipped']),
                    'failed' => count($result['failed']),
                ],
                'updated' => AdminUserResource::collection($result['updated']),
                'skipped' => $result['skipped'],
                'failed' => $result['failed'],
            ],
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
