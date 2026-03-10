<?php

declare(strict_types=1);

namespace App\Http\Resources\Mobile\Quiz;

use App\Models\Quiz;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Quiz
 */
class QuizInfoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->translate('title'),
            'description' => $this->translate('description'),
            'attachable_type' => $this->attachable_type,
            'attachable_id' => $this->attachable_id,
            'passing_score' => (float) $this->passing_score,
            'max_attempts' => $this->max_attempts,
            'time_limit_minutes' => $this->time_limit_minutes,
            'shuffle_questions' => $this->shuffle_questions,
            'shuffle_answers' => $this->shuffle_answers,
            'show_correct_answers' => $this->show_correct_answers,
            'show_score_immediately' => $this->show_score_immediately,
            'is_required' => $this->is_required,
            'is_available' => $this->isAvailable(),
            'available_from' => $this->available_from?->toIso8601String(),
            'available_until' => $this->available_until?->toIso8601String(),
            'remaining_attempts' => $this->getAttribute('remaining_attempts'),
            'best_score' => $this->getAttribute('best_score'),
            'can_attempt' => $this->getAttribute('can_attempt'),
            'total_questions' => $this->getAttribute('total_questions'),
            'total_points' => (float) $this->getAttribute('total_points'),
        ];
    }
}
