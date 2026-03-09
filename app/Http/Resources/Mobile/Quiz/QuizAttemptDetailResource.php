<?php

declare(strict_types=1);

namespace App\Http\Resources\Mobile\Quiz;

use App\Models\QuizAttempt;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin QuizAttempt
 */
class QuizAttemptDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $quiz = $this->quiz;
        $questions = $quiz->questions()->active()->with('answers')->get();

        // Shuffle if configured
        if ($quiz->shuffle_questions) {
            $questions = $questions->shuffle();
        }

        $answeredQuestionIds = $this->answers->pluck('quiz_question_id')->toArray();

        return [
            'id' => $this->id,
            'quiz_id' => $this->quiz_id,
            'quiz_title' => $quiz->translate('title'),
            'attempt_number' => $this->attempt_number,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'started_at' => $this->started_at?->toIso8601String(),
            'time_limit_minutes' => $quiz->time_limit_minutes,
            'remaining_time_seconds' => $this->getRemainingTimeSeconds(),
            'total_questions' => $questions->count(),
            'answered_questions' => count($answeredQuestionIds),
            'questions' => $questions->map(function ($question) use ($answeredQuestionIds) {
                $answers = $question->answers;
                if ($this->quiz->shuffle_answers) {
                    $answers = $answers->shuffle();
                }

                $savedAnswer = $this->answers->where('quiz_question_id', $question->id)->first();

                return [
                    'id' => $question->id,
                    'question' => $question->translate('question'),
                    'question_type' => $question->question_type->value,
                    'question_type_label' => $question->question_type->label(),
                    'points' => (float) $question->points,
                    'is_answered' => in_array($question->id, $answeredQuestionIds),
                    'selected_answer_ids' => $savedAnswer?->selected_answer_ids ?? [],
                    'answers' => $answers->map(fn ($answer) => [
                        'id' => $answer->id,
                        'answer' => $answer->translate('answer'),
                    ]),
                ];
            }),
        ];
    }
}
