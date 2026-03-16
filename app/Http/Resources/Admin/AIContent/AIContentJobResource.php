<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\AIContent;

use App\Models\AIContentJob;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\LearningAsset;
use App\Models\Pdf;
use App\Models\Quiz;
use App\Models\Section;
use App\Models\Video;
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
            'batch_key' => $this->batch_key,
            'source_type' => $this->source_type->value,
            'source_id' => $this->source_id,
            'source_label' => $this->resolveSourceLabel(),
            'target_type' => $this->target_type->value,
            'target_id' => $this->target_id,
            'target_label' => $this->resolveTargetLabel(),
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

    private function resolveSourceLabel(): ?string
    {
        return match ($this->source_type->value) {
            'video' => $this->resolveTranslatableTitle(Video::query()->find($this->source_id)),
            'pdf' => $this->resolveTranslatableTitle(Pdf::query()->find($this->source_id)),
            'section' => $this->resolveTranslatableTitle(Section::query()->find($this->source_id)),
            'course' => $this->resolveTranslatableTitle(Course::query()->find($this->source_id)),
            default => null,
        };
    }

    private function resolveTargetLabel(): ?string
    {
        if ($this->target_id === null) {
            return null;
        }

        return match ($this->target_type->value) {
            'quiz' => $this->resolveTranslatableTitle(Quiz::query()->find($this->target_id)),
            'assignment' => $this->resolveTranslatableTitle(Assignment::query()->find($this->target_id)),
            'summary', 'flashcards', 'interactive_activity' => $this->resolveTranslatableTitle(LearningAsset::query()->find($this->target_id)),
            default => null,
        };
    }

    private function resolveTranslatableTitle(?object $model): ?string
    {
        if ($model === null || ! method_exists($model, 'translate')) {
            return null;
        }

        /** @var mixed $translatable */
        $translatable = $model;
        $title = $translatable->translate('title');

        return is_string($title) ? $title : null;
    }
}
