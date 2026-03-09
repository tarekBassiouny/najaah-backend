<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Quiz;

use App\Models\AIGenerationJob;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AIGenerationJob
 */
class AIGenerationJobResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'quiz_id' => $this->quiz_id,
            'source_type' => $this->source_type,
            'source_id' => $this->source_id,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'questions_requested' => $this->questions_requested,
            'questions_generated' => $this->questions_generated,
            'ai_provider' => $this->ai_provider,
            'ai_model' => $this->ai_model,
            'error_message' => $this->error_message,
            'generated_questions' => $this->when(
                $this->isCompleted(),
                $this->generated_questions
            ),
            'started_at' => $this->started_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
