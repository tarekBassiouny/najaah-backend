<?php

declare(strict_types=1);

namespace App\Http\Resources\Mobile\Assignment;

use App\Models\AssignmentGroup;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AssignmentGroup
 */
class AssignmentGroupResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'assignment_id' => $this->assignment_id,
            'name' => $this->name,
            'created_by' => $this->created_by,
            'creator' => $this->whenLoaded('creator', fn (): array => [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ]),
            'members_count' => $this->members_count ?? $this->members->count(),
            'members' => $this->whenLoaded('members', fn () => $this->members->map(fn ($member): array => [
                'user_id' => $member->user_id,
                'name' => $member->user->name,
                'role' => $member->getRoleLabel(),
                'joined_at' => $member->joined_at?->toIso8601String(),
            ])),
            'has_submission' => $this->whenLoaded('submission', fn (): bool => $this->submission !== null),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
