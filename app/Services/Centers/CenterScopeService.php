<?php

declare(strict_types=1);

namespace App\Services\Centers;

use App\Exceptions\CenterMismatchException;
use App\Models\User;
use App\Services\Centers\Contracts\CenterScopeServiceInterface;
use Illuminate\Database\Eloquent\Model;

class CenterScopeService implements CenterScopeServiceInterface
{
    public function isSystemSuperAdmin(User $user): bool
    {
        return $user->hasRole('super_admin') && ! is_numeric($user->center_id);
    }

    public function isCenterScopedSuperAdmin(User $user): bool
    {
        return $user->hasRole('super_admin') && is_numeric($user->center_id);
    }

    public function resolveAdminCenterId(User $user): ?int
    {
        return is_numeric($user->center_id) ? (int) $user->center_id : null;
    }

    public function assertSameCenter(User $user, Model $model): void
    {
        if ($this->isSystemSuperAdmin($user)) {
            return;
        }

        $modelCenterId = $model->getAttribute('center_id');
        if (! is_numeric($modelCenterId)) {
            $this->deny();
        }

        $this->assertMember($user, (int) $modelCenterId);
    }

    public function assertAdminSameCenter(User $user, Model $model): void
    {
        if ($this->isSystemSuperAdmin($user)) {
            return;
        }

        $modelCenterId = $model->getAttribute('center_id');
        if (! is_numeric($modelCenterId)) {
            $this->deny();
        }

        $this->assertAdminCenterId($user, (int) $modelCenterId);
    }

    public function assertCenterId(User $user, ?int $centerId): void
    {
        if ($this->isSystemSuperAdmin($user)) {
            return;
        }

        if ($centerId === null && $user->is_student && ! is_numeric($user->center_id)) {
            return;
        }

        $this->assertMember($user, $centerId);
    }

    public function assertAdminCenterId(User $user, ?int $centerId): void
    {
        if ($this->isSystemSuperAdmin($user)) {
            return;
        }

        if ($centerId === null) {
            $this->deny();
        }

        $actorCenterId = $this->resolveAdminCenterId($user);
        if ($actorCenterId === null || $actorCenterId !== $centerId) {
            $this->deny();
        }
    }

    /**
     * Get center IDs the admin can access.
     *
     * @return array<int>|null Returns null for super admins (all centers), array of IDs otherwise
     */
    public function getAccessibleCenterIds(User $user): ?array
    {
        if ($this->isSystemSuperAdmin($user)) {
            return null;
        }

        $actorCenterId = $this->resolveAdminCenterId($user);

        return $actorCenterId !== null ? [$actorCenterId] : [];
    }

    public function matchesResolvedApiCenterScope(User $user, ?int $resolvedCenterId): bool
    {
        if ($resolvedCenterId === null) {
            return true;
        }

        $actorCenterId = $this->resolveAdminCenterId($user);

        if ($actorCenterId !== null) {
            return $actorCenterId === $resolvedCenterId;
        }

        return $this->isSystemSuperAdmin($user);
    }

    public function assertResolvedApiCenterScope(User $user, ?int $resolvedCenterId): void
    {
        if ($this->matchesResolvedApiCenterScope($user, $resolvedCenterId)) {
            return;
        }

        $this->deny();
    }

    private function assertMember(User $user, ?int $centerId): void
    {
        if ($centerId === null || ! $user->belongsToCenter($centerId)) {
            $this->deny();
        }
    }

    private function deny(): void
    {
        throw new CenterMismatchException('Resource does not belong to your center.', 403);
    }
}
