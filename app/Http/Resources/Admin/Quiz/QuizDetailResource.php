<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Quiz;

use App\Models\Quiz;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Quiz
 */
class QuizDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'center_id' => $this->center_id,
            'course_id' => $this->course_id,
            'title' => $this->translate('title'),
            'title_translations' => $this->title_translations,
            'description' => $this->translate('description'),
            'description_translations' => $this->description_translations,
            'attachable_type' => $this->attachable_type,
            'attachable_id' => $this->attachable_id,
            'passing_score' => (float) $this->passing_score,
            'max_attempts' => $this->max_attempts,
            'has_unlimited_attempts' => $this->hasUnlimitedAttempts(),
            'attempt_score_policy' => $this->attempt_score_policy->value,
            'attempt_score_policy_label' => $this->attempt_score_policy->label(),
            'time_limit_minutes' => $this->time_limit_minutes,
            'has_time_limit' => $this->hasTimeLimit(),
            'shuffle_questions' => $this->shuffle_questions,
            'shuffle_answers' => $this->shuffle_answers,
            'show_correct_answers' => $this->show_correct_answers,
            'show_score_immediately' => $this->show_score_immediately,
            'is_required' => $this->is_required,
            'is_active' => $this->is_active,
            'is_available' => $this->isAvailable(),
            'available_from' => $this->available_from?->toIso8601String(),
            'available_until' => $this->available_until?->toIso8601String(),
            'order_index' => $this->order_index,
            'total_points' => $this->getTotalPoints(),
            'questions_count' => $this->questions()->count(),
            'attempts_count' => $this->attempts()->count(),
            'questions' => QuizQuestionResource::collection($this->whenLoaded('questions')),
            'creator' => $this->whenLoaded('creator', fn (): array => [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ]),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
