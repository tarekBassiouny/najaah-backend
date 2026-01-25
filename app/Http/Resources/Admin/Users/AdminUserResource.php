<?php

namespace App\Http\Resources\Admin\Users;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class AdminUserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var User $user */
        $user = $this->resource;
        $rolesWithPermissions = null;

        if ($user->relationLoaded('roles')) {
            $rolesWithPermissions = $user->roles
                ->map(static function ($role): array {
                    $permissions = $role->relationLoaded('permissions')
                        ? $role->permissions->pluck('name')->values()
                        : collect();

                    return [
                        'slug' => $role->slug,
                        'permissions' => $permissions,
                    ];
                })
                ->values();
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $user->roles->pluck('slug')->values(),
            'roles_with_permissions' => $rolesWithPermissions,
        ];
    }
}
