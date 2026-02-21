<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Roles;

use App\Http\Resources\Admin\Summary\CenterSummaryResource;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Role
 */
class RoleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Role $role */
        $role = $this->resource;

        return [
            'id' => $role->id,
            'center_id' => $role->center_id,
            'center' => new CenterSummaryResource($this->whenLoaded('center')),
            'is_system_role' => $role->center_id === null,
            'name' => $role->translate('name'),
            'slug' => $role->slug,
            'description' => $role->translate('description'),
            'name_translations' => $role->name_translations,
            'description_translations' => $role->description_translations,
            'permissions' => $role->permissions->pluck('name')->values(),
        ];
    }
}
