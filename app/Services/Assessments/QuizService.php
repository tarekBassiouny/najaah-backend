<?php

declare(strict_types=1);

namespace App\Services\Assessments;

use App\Models\Course;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizQuestion;
use App\Models\User;
use App\Services\Assessments\Contracts\QuizServiceInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

final class QuizService implements QuizServiceInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Course $course, array $data, User $creator): Quiz
    {
        return Quiz::create([
            'center_id' => $course->center_id,
            'course_id' => $course->id,
            'title_translations' => $data['title_translations'],
            'description_translations' => $data['description_translations'] ?? null,
            'attachable_type' => $data['attachable_type'] ?? null,
            'attachable_id' => $data['attachable_id'] ?? null,
            'passing_score' => $data['passing_score'] ?? 70.00,
            'max_attempts' => $data['max_attempts'] ?? 0,
            'attempt_score_policy' => $data['attempt_score_policy'] ?? 0,
            'time_limit_minutes' => $data['time_limit_minutes'] ?? null,
            'shuffle_questions' => $data['shuffle_questions'] ?? false,
            'shuffle_answers' => $data['shuffle_answers'] ?? false,
            'show_correct_answers' => $data['show_correct_answers'] ?? true,
            'show_score_immediately' => $data['show_score_immediately'] ?? true,
            'is_required' => $data['is_required'] ?? false,
            'is_active' => $data['is_active'] ?? false,
            'available_from' => $data['available_from'] ?? null,
            'available_until' => $data['available_until'] ?? null,
            'order_index' => $data['order_index'] ?? 0,
            'created_by' => $creator->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Quiz $quiz, array $data): Quiz
    {
        $quiz->update([
            'title_translations' => $data['title_translations'] ?? $quiz->title_translations,
            'description_translations' => $data['description_translations'] ?? $quiz->description_translations,
            'attachable_type' => $data['attachable_type'] ?? $quiz->attachable_type,
            'attachable_id' => $data['attachable_id'] ?? $quiz->attachable_id,
            'passing_score' => $data['passing_score'] ?? $quiz->passing_score,
            'max_attempts' => $data['max_attempts'] ?? $quiz->max_attempts,
            'attempt_score_policy' => $data['attempt_score_policy'] ?? $quiz->attempt_score_policy,
            'time_limit_minutes' => $data['time_limit_minutes'] ?? $quiz->time_limit_minutes,
            'shuffle_questions' => $data['shuffle_questions'] ?? $quiz->shuffle_questions,
            'shuffle_answers' => $data['shuffle_answers'] ?? $quiz->shuffle_answers,
            'show_correct_answers' => $data['show_correct_answers'] ?? $quiz->show_correct_answers,
            'show_score_immediately' => $data['show_score_immediately'] ?? $quiz->show_score_immediately,
            'is_required' => $data['is_required'] ?? $quiz->is_required,
            'is_active' => $data['is_active'] ?? $quiz->is_active,
            'available_from' => $data['available_from'] ?? $quiz->available_from,
            'available_until' => $data['available_until'] ?? $quiz->available_until,
            'order_index' => $data['order_index'] ?? $quiz->order_index,
        ]);

        return $quiz->fresh() ?? $quiz;
    }

    public function delete(Quiz $quiz): void
    {
        $quiz->delete();
    }

    public function duplicate(Quiz $quiz, User $creator): Quiz
    {
        return DB::transaction(function () use ($quiz, $creator): Quiz {
            $quiz->load(['questions.answers']);

            $newQuiz = $quiz->replicate(['created_by']);
            $newQuiz->created_by = $creator->id;
            $newQuiz->is_active = false;
            $newQuiz->title_translations = array_map(
                fn (string $title): string => $title . ' (Copy)',
                $quiz->title_translations
            );
            $newQuiz->save();

            foreach ($quiz->questions as $question) {
                $newQuestion = $question->replicate();
                $newQuestion->quiz_id = $newQuiz->id;
                $newQuestion->save();

                foreach ($question->answers as $answer) {
                    $newAnswer = $answer->replicate();
                    $newAnswer->quiz_question_id = $newQuestion->id;
                    $newAnswer->save();
                }
            }

            return $newQuiz;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function addQuestion(Quiz $quiz, array $data): QuizQuestion
    {
        return DB::transaction(function () use ($quiz, $data): QuizQuestion {
            $maxOrder = $quiz->questions()->max('order_index') ?? -1;

            $question = QuizQuestion::create([
                'quiz_id' => $quiz->id,
                'question_translations' => $data['question_translations'],
                'question_type' => $data['question_type'] ?? 0,
                'explanation_translations' => $data['explanation_translations'] ?? null,
                'points' => $data['points'] ?? 1.00,
                'order_index' => $data['order_index'] ?? ($maxOrder + 1),
                'is_active' => $data['is_active'] ?? true,
            ]);

            if (isset($data['answers']) && is_array($data['answers'])) {
                foreach ($data['answers'] as $index => $answerData) {
                    QuizAnswer::create([
                        'quiz_question_id' => $question->id,
                        'answer_translations' => $answerData['answer_translations'],
                        'is_correct' => $answerData['is_correct'] ?? false,
                        'order_index' => $answerData['order_index'] ?? $index,
                    ]);
                }
            }

            return $question->load('answers');
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateQuestion(QuizQuestion $question, array $data): QuizQuestion
    {
        return DB::transaction(function () use ($question, $data): QuizQuestion {
            $question->update([
                'question_translations' => $data['question_translations'] ?? $question->question_translations,
                'question_type' => $data['question_type'] ?? $question->question_type,
                'explanation_translations' => $data['explanation_translations'] ?? $question->explanation_translations,
                'points' => $data['points'] ?? $question->points,
                'order_index' => $data['order_index'] ?? $question->order_index,
                'is_active' => $data['is_active'] ?? $question->is_active,
            ]);

            if (isset($data['answers']) && is_array($data['answers'])) {
                $question->answers()->delete();

                foreach ($data['answers'] as $index => $answerData) {
                    QuizAnswer::create([
                        'quiz_question_id' => $question->id,
                        'answer_translations' => $answerData['answer_translations'],
                        'is_correct' => $answerData['is_correct'] ?? false,
                        'order_index' => $answerData['order_index'] ?? $index,
                    ]);
                }
            }

            return $question->fresh()?->load('answers') ?? $question;
        });
    }

    public function deleteQuestion(QuizQuestion $question): void
    {
        $question->delete();
    }

    /**
     * @param  array<int>  $questionIds
     */
    public function reorderQuestions(Quiz $quiz, array $questionIds): void
    {
        DB::transaction(function () use ($quiz, $questionIds): void {
            foreach ($questionIds as $index => $questionId) {
                QuizQuestion::where('id', $questionId)
                    ->where('quiz_id', $quiz->id)
                    ->update(['order_index' => $index]);
            }
        });
    }

    public function isAvailable(Quiz $quiz, User $student): bool
    {
        return $quiz->isAvailable();
    }

    public function canAttempt(Quiz $quiz, User $student): bool
    {
        if (! $this->isAvailable($quiz, $student)) {
            return false;
        }

        $remaining = $this->getRemainingAttempts($quiz, $student);

        return $remaining === null || $remaining > 0;
    }

    public function getRemainingAttempts(Quiz $quiz, User $student): ?int
    {
        if ($quiz->hasUnlimitedAttempts()) {
            return null;
        }

        $completedAttempts = $quiz->attempts()
            ->where('user_id', $student->id)
            ->completed()
            ->count();

        return max(0, $quiz->max_attempts - $completedAttempts);
    }

    /**
     * @return Collection<int, Quiz>
     */
    public function getQuizzesForCourse(Course $course, bool $activeOnly = false): Collection
    {
        $query = Quiz::where('course_id', $course->id)
            ->orderBy('order_index');

        if ($activeOnly) {
            $query->active();
        }

        return $query->get();
    }

    public function getQuizWithQuestions(Quiz $quiz): Quiz
    {
        return $quiz->load(['questions' => function ($query): void {
            $query->where('is_active', true)->orderBy('order_index');
        }, 'questions.answers' => function ($query): void {
            $query->orderBy('order_index');
        }]);
    }
}
