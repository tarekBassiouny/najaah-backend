<?php

declare(strict_types=1);

namespace App\Services\Assessments\Contracts;

use App\Models\Enrollment;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizAttemptAnswer;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface QuizAttemptServiceInterface
{
    /**
     * Start a new quiz attempt for a student.
     */
    public function start(Quiz $quiz, User $student, Enrollment $enrollment): QuizAttempt;

    /**
     * Save an answer for a question in an attempt.
     *
     * @param  array<int>  $answerIds
     */
    public function saveAnswer(QuizAttempt $attempt, QuizQuestion $question, array $answerIds): QuizAttemptAnswer;

    /**
     * Submit an attempt for grading.
     */
    public function submit(QuizAttempt $attempt): QuizAttempt;

    /**
     * Auto-submit all timed-out attempts.
     * Returns the number of attempts that were auto-submitted.
     */
    public function autoSubmitTimedOut(): int;

    /**
     * Grade an attempt (calculate score and pass/fail).
     */
    public function grade(QuizAttempt $attempt): QuizAttempt;

    /**
     * Calculate the final score for a student on a quiz based on score policy.
     */
    public function calculateFinalScore(Quiz $quiz, User $student): ?float;

    /**
     * Get all attempts for a student on a quiz.
     *
     * @return Collection<int, QuizAttempt>
     */
    public function getAttempts(Quiz $quiz, User $student): Collection;

    /**
     * Get detailed attempt information with answers.
     *
     * @return array<string, mixed>
     */
    public function getAttemptDetails(QuizAttempt $attempt): array;

    /**
     * Get the current in-progress attempt for a student on a quiz.
     */
    public function getCurrentAttempt(Quiz $quiz, User $student): ?QuizAttempt;

    /**
     * Check if student has passed the quiz based on any completed attempt.
     */
    public function hasPassed(Quiz $quiz, User $student): bool;
}
