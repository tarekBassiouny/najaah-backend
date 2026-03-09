<?php

declare(strict_types=1);

namespace App\Services\Assessments;

use App\Enums\AttemptScorePolicy;
use App\Enums\QuestionType;
use App\Enums\QuizAttemptStatus;
use App\Models\Enrollment;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizAttemptAnswer;
use App\Models\QuizQuestion;
use App\Models\User;
use App\Services\Assessments\Contracts\QuizAttemptServiceInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

final class QuizAttemptService implements QuizAttemptServiceInterface
{
    public function start(Quiz $quiz, User $student, Enrollment $enrollment): QuizAttempt
    {
        $existingAttempt = $this->getCurrentAttempt($quiz, $student);
        if ($existingAttempt !== null) {
            return $existingAttempt;
        }

        $attemptNumber = $quiz->attempts()
            ->where('user_id', $student->id)
            ->count() + 1;

        return QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'user_id' => $student->id,
            'enrollment_id' => $enrollment->id,
            'center_id' => $quiz->center_id,
            'attempt_number' => $attemptNumber,
            'status' => QuizAttemptStatus::InProgress,
            'started_at' => now(),
            'points_possible' => $quiz->getTotalPoints(),
        ]);
    }

    /**
     * @param  array<int>  $answerIds
     */
    public function saveAnswer(QuizAttempt $attempt, QuizQuestion $question, array $answerIds): QuizAttemptAnswer
    {
        $existingAnswer = QuizAttemptAnswer::where('quiz_attempt_id', $attempt->id)
            ->where('quiz_question_id', $question->id)
            ->first();

        if ($existingAnswer !== null) {
            $existingAnswer->update([
                'selected_answer_ids' => $answerIds,
                'answered_at' => now(),
            ]);

            return $existingAnswer;
        }

        return QuizAttemptAnswer::create([
            'quiz_attempt_id' => $attempt->id,
            'quiz_question_id' => $question->id,
            'selected_answer_ids' => $answerIds,
            'answered_at' => now(),
        ]);
    }

    public function submit(QuizAttempt $attempt): QuizAttempt
    {
        if (! $attempt->isInProgress()) {
            return $attempt;
        }

        $attempt->update([
            'status' => QuizAttemptStatus::Submitted,
            'submitted_at' => now(),
            'time_spent_seconds' => now()->diffInSeconds($attempt->started_at),
        ]);

        return $this->grade($attempt);
    }

    public function autoSubmitTimedOut(): int
    {
        $count = 0;

        $timedOutAttempts = QuizAttempt::where('status', QuizAttemptStatus::InProgress)
            ->whereNotNull('started_at')
            ->with('quiz')
            ->get()
            ->filter(fn (QuizAttempt $attempt): bool => $attempt->hasTimeLimitExpired());

        foreach ($timedOutAttempts as $attempt) {
            $attempt->update([
                'status' => QuizAttemptStatus::TimedOut,
                'submitted_at' => now(),
                'time_spent_seconds' => $attempt->quiz->time_limit_minutes * 60,
            ]);

            $this->grade($attempt);
            $count++;
        }

        return $count;
    }

    public function grade(QuizAttempt $attempt): QuizAttempt
    {
        return DB::transaction(function () use ($attempt): QuizAttempt {
            $attempt->load(['answers', 'quiz.questions.answers']);

            $totalPoints = 0.0;
            $earnedPoints = 0.0;

            foreach ($attempt->quiz->questions as $question) {
                if (! $question->is_active) {
                    continue;
                }

                $totalPoints += (float) $question->points;

                $attemptAnswer = $attempt->answers
                    ->firstWhere('quiz_question_id', $question->id);

                if ($attemptAnswer === null) {
                    continue;
                }

                $isCorrect = $this->checkAnswerCorrectness($question, $attemptAnswer->selected_answer_ids ?? []);
                $pointsEarned = $isCorrect ? (float) $question->points : 0.0;

                $attemptAnswer->update([
                    'is_correct' => $isCorrect,
                    'points_earned' => $pointsEarned,
                ]);

                $earnedPoints += $pointsEarned;
            }

            $score = $totalPoints > 0 ? ($earnedPoints / $totalPoints) * 100 : 0;
            $passed = $score >= (float) $attempt->quiz->passing_score;

            $attempt->update([
                'status' => QuizAttemptStatus::Graded,
                'points_earned' => $earnedPoints,
                'points_possible' => $totalPoints,
                'score' => round($score, 2),
                'passed' => $passed,
            ]);

            return $attempt->fresh() ?? $attempt;
        });
    }

    /**
     * @param  array<int>  $selectedAnswerIds
     */
    private function checkAnswerCorrectness(QuizQuestion $question, array $selectedAnswerIds): bool
    {
        if (empty($selectedAnswerIds)) {
            return false;
        }

        $correctAnswerIds = $question->answers
            ->where('is_correct', true)
            ->pluck('id')
            ->toArray();

        if (empty($correctAnswerIds)) {
            return false;
        }

        if ($question->question_type === QuestionType::SingleChoice) {
            return count($selectedAnswerIds) === 1
                && in_array($selectedAnswerIds[0], $correctAnswerIds, true);
        }

        sort($selectedAnswerIds);
        sort($correctAnswerIds);

        return $selectedAnswerIds === $correctAnswerIds;
    }

    public function calculateFinalScore(Quiz $quiz, User $student): ?float
    {
        $completedAttempts = $quiz->attempts()
            ->where('user_id', $student->id)
            ->where('status', QuizAttemptStatus::Graded)
            ->get();

        if ($completedAttempts->isEmpty()) {
            return null;
        }

        return match ($quiz->attempt_score_policy) {
            AttemptScorePolicy::Best => (float) $completedAttempts->max('score'),
            AttemptScorePolicy::Latest => (float) $completedAttempts->sortByDesc('submitted_at')->first()->score,
            AttemptScorePolicy::Average => round($completedAttempts->avg('score'), 2),
        };
    }

    /**
     * @return Collection<int, QuizAttempt>
     */
    public function getAttempts(Quiz $quiz, User $student): Collection
    {
        return $quiz->attempts()
            ->where('user_id', $student->id)
            ->orderBy('attempt_number')
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    public function getAttemptDetails(QuizAttempt $attempt): array
    {
        $attempt->load(['answers.question.answers', 'quiz']);

        $questions = [];
        foreach ($attempt->answers as $attemptAnswer) {
            $question = $attemptAnswer->question;
            $questions[] = [
                'question_id' => $question->id,
                'question' => $question->translate('question'),
                'question_type' => $question->question_type->value,
                'points' => (float) $question->points,
                'selected_answer_ids' => $attemptAnswer->selected_answer_ids ?? [],
                'is_correct' => $attemptAnswer->is_correct,
                'points_earned' => (float) $attemptAnswer->points_earned,
                'explanation' => $attempt->quiz->show_correct_answers ? $question->translate('explanation') : null,
                'answers' => $question->answers->map(fn ($answer): array => [
                    'id' => $answer->id,
                    'answer' => $answer->translate('answer'),
                    'is_correct' => $attempt->quiz->show_correct_answers ? $answer->is_correct : null,
                ])->toArray(),
            ];
        }

        return [
            'attempt_id' => $attempt->id,
            'attempt_number' => $attempt->attempt_number,
            'status' => $attempt->status->value,
            'started_at' => $attempt->started_at?->toIso8601String(),
            'submitted_at' => $attempt->submitted_at?->toIso8601String(),
            'time_spent_seconds' => $attempt->time_spent_seconds,
            'score' => $attempt->score !== null ? (float) $attempt->score : null,
            'points_earned' => (float) $attempt->points_earned,
            'points_possible' => (float) $attempt->points_possible,
            'passed' => $attempt->passed,
            'questions' => $questions,
        ];
    }

    public function getCurrentAttempt(Quiz $quiz, User $student): ?QuizAttempt
    {
        return $quiz->attempts()
            ->where('user_id', $student->id)
            ->where('status', QuizAttemptStatus::InProgress)
            ->first();
    }

    public function hasPassed(Quiz $quiz, User $student): bool
    {
        return $quiz->attempts()
            ->where('user_id', $student->id)
            ->where('passed', true)
            ->exists();
    }
}
