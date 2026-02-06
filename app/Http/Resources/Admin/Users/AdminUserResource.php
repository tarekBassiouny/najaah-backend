<?php

namespace App\Http\Resources\Admin\Users;

use App\Enums\UserStatus;
use App\Http\Resources\Admin\Summary\CenterSummaryResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

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
        $status = $user->status instanceof UserStatus
            ? $user->status
            : ($user->status !== null ? UserStatus::tryFrom((int) $user->status) : null);
        $rolesWithPermissions = null;

        if ($user->relationLoaded('roles')) {
            $rolesWithPermissions = $user->roles
                ->map(static function ($role): array {
                    $permissions = $role->relationLoaded('permissions')
                        ? $role->permissions->pluck('name')->values()
                        : collect();

                    return [
                        'name' => Str::title((string) $role->name),
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
            'username' => $user->username,
            'phone' => $user->phone,
            'center_id' => $user->center_id,
            'country_code' => $user->country_code,
            'last_active' => $user->last_login_at?->format('d-m-Y'),
            'status' => $status?->value ?? $user->status,
            'status_key' => $status !== null ? Str::snake($status->name) : null,
            'status_label' => $status?->name,
            'center' => new CenterSummaryResource($this->whenLoaded('center')),
            'roles' => $user->roles->pluck('slug')->values(),
            'roles_with_permissions' => $rolesWithPermissions,
        ];
    }
}
