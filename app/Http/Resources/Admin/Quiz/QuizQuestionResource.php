<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Quiz;

use App\Models\QuizQuestion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin QuizQuestion
 */
class QuizQuestionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'quiz_id' => $this->quiz_id,
            'question' => $this->translate('question'),
            'question_translations' => $this->question_translations,
            'question_type' => $this->question_type->value,
            'question_type_label' => $this->question_type->label(),
            'allows_multiple_answers' => $this->allowsMultipleAnswers(),
            'explanation' => $this->translate('explanation'),
            'explanation_translations' => $this->explanation_translations,
            'points' => (float) $this->points,
            'order_index' => $this->order_index,
            'is_active' => $this->is_active,
            'ai_generated' => $this->ai_generated,
            'ai_source_type' => $this->ai_source_type,
            'ai_source_id' => $this->ai_source_id,
            'answers' => QuizAnswerResource::collection($this->whenLoaded('answers')),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
