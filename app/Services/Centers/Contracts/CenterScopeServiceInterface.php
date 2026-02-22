<?php

declare(strict_types=1);

namespace App\Services\Centers\Contracts;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

interface CenterScopeServiceInterface
{
    /**
     * Check if the user is a system-wide super admin (no center_id).
     */
    public function isSystemSuperAdmin(User $user): bool;

    /**
     * Check if the user has center-scoped super-admin privileges (has center_id).
     */
    public function isCenterScopedSuperAdmin(User $user): bool;

    /**
     * Resolve the admin's center ID from the user model.
     */
    public function resolveAdminCenterId(User $user): ?int;

    /**
     * Assert the user belongs to the same center as the model.
     *
     * @throws \App\Exceptions\CenterMismatchException
     */
    public function assertSameCenter(User $user, Model $model): void;

    /**
     * Assert the admin user belongs to the same center as the model.
     *
     * @throws \App\Exceptions\CenterMismatchException
     */
    public function assertAdminSameCenter(User $user, Model $model): void;

    /**
     * Assert the user has access to the specified center ID.
     *
     * @throws \App\Exceptions\CenterMismatchException
     */
    public function assertCenterId(User $user, ?int $centerId): void;

    /**
     * Assert the admin has access to the specified center ID.
     *
     * @throws \App\Exceptions\CenterMismatchException
     */
    public function assertAdminCenterId(User $user, ?int $centerId): void;

    /**
     * Get center IDs the admin can access.
     *
     * @return array<int>|null Returns null for super admins (all centers), array of IDs otherwise
     */
    public function getAccessibleCenterIds(User $user): ?array;

    /**
     * Check if user's scope matches the resolved API center scope.
     */
    public function matchesResolvedApiCenterScope(User $user, ?int $resolvedCenterId): bool;

    /**
     * Assert that user's scope matches the resolved API center scope.
     *
     * @throws \App\Exceptions\CenterMismatchException
     */
    public function assertResolvedApiCenterScope(User $user, ?int $resolvedCenterId): void;
}
