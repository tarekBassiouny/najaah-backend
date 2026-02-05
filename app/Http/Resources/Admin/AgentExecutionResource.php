<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use App\Enums\AgentExecutionStatus;
use App\Http\Resources\Admin\Summary\CenterSummaryResource;
use App\Http\Resources\Admin\Summary\UserSummaryResource;
use App\Models\AgentExecution;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

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
        $status = $execution->status instanceof AgentExecutionStatus
            ? $execution->status
            : AgentExecutionStatus::from((int) $execution->status);

        return [
            'id' => $execution->id,
            'center' => new CenterSummaryResource($this->whenLoaded('center')),
            'agent_type' => Str::snake($execution->agent_type->name),
            'agent_type_label' => $execution->agent_type->name,
            'target_type' => $execution->target_type,
            'target_type_class' => $execution->target_type !== null ? class_basename($execution->target_type) : null,
            'target_id' => $execution->target_id,
            'status' => $status->value,
            'status_key' => Str::snake($status->name),
            'status_label' => $status->name,
            'context' => $execution->context,
            'result' => $execution->result,
            'steps_completed' => $execution->steps_completed ?? [],
            'started_at' => $execution->started_at?->toIso8601String(),
            'completed_at' => $execution->completed_at?->toIso8601String(),
            'initiator' => new UserSummaryResource($this->whenLoaded('initiator')),
            'target' => $this->whenLoaded('target', function () use ($execution): ?array {
                return $execution->target ? [
                    'id' => $execution->target->getKey(),
                    'type' => class_basename($execution->target),
                ] : null;
            }),
            'created_at' => $execution->created_at?->toIso8601String(),
            'updated_at' => $execution->updated_at?->toIso8601String(),
        ];
    }
}
