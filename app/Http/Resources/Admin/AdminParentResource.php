<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class AdminParentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var User $user */
        $user = $this->resource;

        return [
            'id' => $user->id,
            'name' => $user->name,
            'phone' => $user->phone,
            'country_code' => $user->country_code,
            'center_id' => $user->center_id,
            'is_parent' => $user->is_parent,
            'is_student' => $user->is_student,
            'active_links_count' => (int) $user->getAttribute('active_links_count'),
            'created_at' => $user->created_at?->toISOString(),
        ];
    }
}
