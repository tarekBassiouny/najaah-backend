<?php

declare(strict_types=1);

namespace App\Services\AdminUsers\Contracts;

use App\Filters\Admin\AdminUserFilters;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AdminUserServiceInterface
{
    /**
     * @return LengthAwarePaginator<User>
     */
    public function list(AdminUserFilters $filters, ?User $actor = null): LengthAwarePaginator;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?User $actor = null, ?int $forcedCenterId = null): User;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, array $data, ?User $actor = null, ?int $forcedCenterId = null): User;

    public function updateStatus(User $user, int $status, ?User $actor = null, ?int $forcedCenterId = null): User;

    /**
     * @param  array<int, int>  $userIds
     * @return array{
     *   updated: array<int, User>,
     *   skipped: array<int, array{user_id: int, reason: string}>,
     *   failed: array<int, array{user_id: int, reason: string}>
     * }
     */
    public function bulkUpdateStatus(array $userIds, int $status, ?User $actor = null, ?int $forcedCenterId = null): array;

    public function assignCenter(User $user, int $centerId, ?User $actor = null): User;

    /**
     * @param  array<int, array{user_id:int, center_id:int}>  $assignments
     * @return array{
     *   updated: array<int, User>,
     *   skipped: array<int, array{user_id: int, center_id: int, reason: string}>,
     *   failed: array<int, array{user_id: int, center_id: int, reason: string}>
     * }
     */
    public function bulkAssignCenters(array $assignments, ?User $actor = null): array;

    public function delete(User $user, ?User $actor = null, ?int $forcedCenterId = null): void;

    /**
     * @param  array<int, int>  $roleIds
     */
    public function syncRoles(User $user, array $roleIds, ?User $actor = null, ?int $forcedCenterId = null): User;

    /**
     * @param  array<int, int>  $userIds
     * @param  array<int, int>  $roleIds
     * @return array{
     *   updated: array<int, User>,
     *   skipped: array<int, array{user_id: int, reason: string}>,
     *   failed: array<int, array{user_id: int, reason: string}>
     * }
     */
    public function bulkSyncRoles(array $userIds, array $roleIds, ?User $actor = null, ?int $forcedCenterId = null): array;
}
