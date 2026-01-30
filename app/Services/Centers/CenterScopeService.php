<?php

declare(strict_types=1);

namespace App\Services\Centers;

use App\Exceptions\CenterMismatchException;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class CenterScopeService
{
    public function assertSameCenter(User $user, Model $model): void
    {
        if ($this->isSuperAdmin($user)) {
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
        if ($this->isSuperAdmin($user)) {
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
        if ($this->isSuperAdmin($user)) {
            return;
        }

        $this->assertMember($user, $centerId);
    }

    public function assertAdminCenterId(User $user, ?int $centerId): void
    {
        if ($this->isSuperAdmin($user)) {
            return;
        }

        if ($centerId === null) {
            $this->deny();
        }

        if (! $user->isAdminOfCenter($centerId)) {
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
        if ($this->isSuperAdmin($user)) {
            return null;
        }

        return $user->centers()
            ->wherePivotIn('type', ['admin', 'owner'])
            ->pluck('centers.id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    private function assertMember(User $user, ?int $centerId): void
    {
        if ($centerId === null || ! $user->belongsToCenter($centerId)) {
            $this->deny();
        }
    }

    private function isSuperAdmin(User $user): bool
    {
        return $user->hasRole('super_admin');
    }

    private function deny(): void
    {
        throw new CenterMismatchException('Resource does not belong to your center.', 403);
    }
}
