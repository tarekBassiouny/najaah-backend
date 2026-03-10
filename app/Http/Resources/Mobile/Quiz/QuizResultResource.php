<?php

declare(strict_types=1);

namespace App\Http\Resources\Mobile\Quiz;

use App\Models\QuizAttempt;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin QuizAttempt
 */
class QuizResultResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $quiz = $this->quiz;
        $showCorrectAnswers = $quiz->show_correct_answers;

        $result = [
            'id' => $this->id,
            'quiz_id' => $this->quiz_id,
            'quiz_title' => $quiz->translate('title'),
            'attempt_number' => $this->attempt_number,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'started_at' => $this->started_at?->toIso8601String(),
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'time_spent_seconds' => $this->time_spent_seconds,
            'score' => $quiz->show_score_immediately ? (float) $this->score : null,
            'points_earned' => $quiz->show_score_immediately ? (float) $this->points_earned : null,
            'points_possible' => (float) $this->points_possible,
            'passed' => $quiz->show_score_immediately ? $this->passed : null,
            'passing_score' => (float) $quiz->passing_score,
        ];

        // Include detailed results if show_correct_answers is enabled
        if ($showCorrectAnswers) {
            $result['questions'] = $this->answers->map(function ($attemptAnswer): array {
                $question = $attemptAnswer->question;
                $correctAnswerIds = $question->answers->where('is_correct', true)->pluck('id')->toArray();

                return [
                    'id' => $question->id,
                    'question' => $question->translate('question'),
                    'points' => (float) $question->points,
                    'points_earned' => (float) $attemptAnswer->points_earned,
                    'is_correct' => $attemptAnswer->is_correct,
                    'selected_answer_ids' => $attemptAnswer->selected_answer_ids,
                    'correct_answer_ids' => $correctAnswerIds,
                    'explanation' => $question->translate('explanation'),
                    'answers' => $question->answers->map(fn ($answer): array => [
                        'id' => $answer->id,
                        'answer' => $answer->translate('answer'),
                        'is_correct' => $answer->is_correct,
                    ]),
                ];
            });
        }

        return $result;
    }
}
