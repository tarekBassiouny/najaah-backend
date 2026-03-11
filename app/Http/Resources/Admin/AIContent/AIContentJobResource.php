<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\AIContent;

use App\Models\AIContentJob;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AIContentJob
 */
class AIContentJobResource extends JsonResource
{
    /**
     * @return array<string,mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'center_id' => $this->center_id,
            'course_id' => $this->course_id,
            'source_type' => $this->source_type->value,
            'source_id' => $this->source_id,
            'target_type' => $this->target_type->value,
            'target_id' => $this->target_id,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'generation_config' => $this->generation_config,
            'generated_payload' => $this->generated_payload,
            'reviewed_payload' => $this->reviewed_payload,
            'ai_provider' => $this->ai_provider,
            'ai_model' => $this->ai_model,
            'estimated_input_tokens' => $this->estimated_input_tokens,
            'estimated_output_tokens' => $this->estimated_output_tokens,
            'error_message' => $this->error_message,
            'started_at' => $this->started_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'published_at' => $this->published_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
