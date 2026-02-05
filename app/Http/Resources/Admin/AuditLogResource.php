<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use App\Http\Resources\Admin\Summary\UserSummaryResource;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AuditLog
 */
class AuditLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var AuditLog $log */
        $log = $this->resource;

        return [
            'id' => $log->id,
            'user_id' => $log->user_id,
            'center_id' => $log->user?->center_id,
            'user' => new UserSummaryResource($this->whenLoaded('user')),
            'action' => $log->action,
            'entity_type' => $log->entity_type,
            'entity_id' => $log->entity_id,
            'entity_label' => $this->whenLoaded('entity', function () use ($log) {
                return $log->entity?->title
                    ?? $log->entity?->name
                    ?? $log->entity?->slug
                    ?? $log->entity?->id;
            }),
            'metadata' => $log->metadata,
            'created_at' => $log->created_at,
        ];
    }
}
