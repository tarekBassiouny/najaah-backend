<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use App\Models\ParentStudentLink;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ParentStudentLink
 */
class AdminParentStudentLinkResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var ParentStudentLink $link */
        $link = $this->resource;

        return [
            'id' => $link->id,
            'parent' => $link->relationLoaded('parent') && $link->parent instanceof User ? [
                'id' => $link->parent->id,
                'name' => $link->parent->name,
                'phone' => $link->parent->phone,
            ] : null,
            'student' => $link->relationLoaded('student') && $link->student instanceof User ? [
                'id' => $link->student->id,
                'name' => $link->student->name,
                'phone' => $link->student->phone,
            ] : null,
            'center_id' => $link->center_id,
            'status' => $link->status->name,
            'link_method' => $link->link_method->name,
            'linked_by' => $link->relationLoaded('linkedByUser') && $link->linkedByUser instanceof User ? [
                'id' => $link->linkedByUser->id,
                'name' => $link->linkedByUser->name,
            ] : null,
            'linked_at' => $link->linked_at->toISOString(),
            'created_at' => $link->created_at?->toISOString(),
        ];
    }
}
