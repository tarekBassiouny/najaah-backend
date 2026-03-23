<?php

declare(strict_types=1);

namespace App\Http\Resources\Parent;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizAttemptAnswer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin QuizAttempt
 */
class ParentQuizAttemptResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var QuizAttempt $attempt */
        $attempt = $this->resource;
        $quiz = $attempt->quiz;

        return [
            'id' => $attempt->id,
            'quiz' => $quiz instanceof Quiz ? [
                'id' => $quiz->id,
                'title' => $quiz->translate('title'),
            ] : null,
            'attempt_number' => $attempt->attempt_number,
            'status' => $attempt->status->name,
            'started_at' => $attempt->started_at?->toISOString(),
            'submitted_at' => $attempt->submitted_at?->toISOString(),
            'time_spent_seconds' => $attempt->time_spent_seconds,
            'score' => $attempt->score,
            'points_earned' => $attempt->points_earned,
            'points_possible' => $attempt->points_possible,
            'passed' => $attempt->passed,
            'answers' => $attempt->relationLoaded('answers')
                ? $attempt->answers->map(fn (QuizAttemptAnswer $answer): array => [
                    'question_id' => $answer->question_id,
                    'question_text' => $answer->question->translate('question_text'),
                    'selected_option_id' => $answer->selected_option_id,
                    'is_correct' => $answer->is_correct,
                    'points_earned' => $answer->points_earned,
                ])
                : [],
        ];
    }
}
