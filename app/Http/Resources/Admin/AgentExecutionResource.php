<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use App\Http\Resources\Admin\Centers\CenterResource;
use App\Http\Resources\Admin\Users\AdminUserResource;
use App\Models\AgentExecution;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AgentExecution
 */
class AgentExecutionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var AgentExecution $execution */
        $execution = $this->resource;

        return [
            'id' => $execution->id,
            'center_id' => $execution->center_id,
            'agent_type' => $execution->agent_type->value,
            'target_type' => $execution->target_type,
            'target_id' => $execution->target_id,
            'status' => $execution->statusLabel(),
            'status_code' => $execution->status->value,
            'context' => $execution->context,
            'result' => $execution->result,
            'steps_completed' => $execution->steps_completed ?? [],
            'started_at' => $execution->started_at?->toIso8601String(),
            'completed_at' => $execution->completed_at?->toIso8601String(),
            'initiated_by' => $execution->initiated_by,
            'created_at' => $execution->created_at?->toIso8601String(),
            'updated_at' => $execution->updated_at?->toIso8601String(),
            'center' => new CenterResource($this->whenLoaded('center')),
            'initiator' => new AdminUserResource($this->whenLoaded('initiator')),
            'target' => $this->whenLoaded('target', function () use ($execution) {
                return $execution->target ? [
                    'id' => $execution->target->getKey(),
                    'type' => get_class($execution->target),
                ] : null;
            }),
        ];
    }
}
