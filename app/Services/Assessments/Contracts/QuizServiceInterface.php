<?php

declare(strict_types=1);

namespace App\Services\Assessments\Contracts;

use App\Models\Course;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface QuizServiceInterface
{
    /**
     * Create a new quiz for a course.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(Course $course, array $data, User $creator): Quiz;

    /**
     * Update an existing quiz.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Quiz $quiz, array $data): Quiz;

    /**
     * Delete a quiz (soft delete).
     */
    public function delete(Quiz $quiz): void;

    /**
     * Duplicate a quiz with all its questions and answers.
     */
    public function duplicate(Quiz $quiz, User $creator): Quiz;

    /**
     * Add a question to a quiz.
     *
     * @param  array<string, mixed>  $data
     */
    public function addQuestion(Quiz $quiz, array $data): QuizQuestion;

    /**
     * Update an existing question.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateQuestion(QuizQuestion $question, array $data): QuizQuestion;

    /**
     * Delete a question (soft delete).
     */
    public function deleteQuestion(QuizQuestion $question): void;

    /**
     * Reorder questions in a quiz.
     *
     * @param  array<int>  $questionIds  Ordered list of question IDs
     */
    public function reorderQuestions(Quiz $quiz, array $questionIds): void;

    /**
     * Check if a quiz is currently available for a student.
     */
    public function isAvailable(Quiz $quiz, User $student): bool;

    /**
     * Check if a student can start a new attempt on a quiz.
     */
    public function canAttempt(Quiz $quiz, User $student): bool;

    /**
     * Get the number of remaining attempts for a student.
     * Returns null if unlimited attempts are allowed.
     */
    public function getRemainingAttempts(Quiz $quiz, User $student): ?int;

    /**
     * Get all quizzes for a course.
     *
     * @return Collection<int, Quiz>
     */
    public function getQuizzesForCourse(Course $course, bool $activeOnly = false): Collection;

    /**
     * Get quiz with questions and answers loaded.
     */
    public function getQuizWithQuestions(Quiz $quiz): Quiz;
}
