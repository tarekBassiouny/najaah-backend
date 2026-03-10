<?php

declare(strict_types=1);

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Resources\Mobile\Quiz\QuizInfoResource;
use App\Http\Resources\Mobile\Quiz\QuizListResource;
use App\Models\Course;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Services\Assessments\Contracts\QuizAttemptServiceInterface;
use App\Services\Assessments\Contracts\QuizServiceInterface;
use Illuminate\Http\JsonResponse;

class QuizController extends Controller
{
    public function __construct(
        private readonly QuizServiceInterface $quizService,
        private readonly QuizAttemptServiceInterface $attemptService
    ) {}

    /**
     * List available quizzes for a course.
     */
    public function index(int $centerId, Course $course): JsonResponse
    {
        /** @var User|null $student */
        $student = request()->user();

        if (! $student instanceof User || $student->is_student === false) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Only students can access quizzes.',
                ],
            ], 403);
        }

        // Verify student is enrolled in this course
        $enrollment = $student->enrollments()
            ->where('course_id', $course->id)
            ->where('center_id', $centerId)
            ->active()
            ->first();

        if (! $enrollment) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_ENROLLED',
                    'message' => 'You are not enrolled in this course.',
                ],
            ], 403);
        }

        $quizzes = Quiz::query()
            ->where('course_id', $course->id)
            ->where('center_id', $centerId)
            ->where('is_active', true)
            ->orderBy('order_index')
            ->get();

        // Add attempt info for each quiz
        $quizzes->each(function (Quiz $quiz) use ($student): void {
            $quiz->setAttribute('remaining_attempts', $this->quizService->getRemainingAttempts($quiz, $student));
            $quiz->setAttribute('best_score', $this->attemptService->calculateFinalScore($quiz, $student));
            $quiz->setAttribute('can_attempt', $this->quizService->canAttempt($quiz, $student));
        });

        return response()->json([
            'success' => true,
            'message' => 'Operation completed',
            'data' => QuizListResource::collection($quizzes),
        ]);
    }

    /**
     * Show quiz info before starting.
     */
    public function show(int $centerId, Quiz $quiz): JsonResponse
    {
        /** @var User|null $student */
        $student = request()->user();

        if (! $student instanceof User || $student->is_student === false) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Only students can access quizzes.',
                ],
            ], 403);
        }

        if ($quiz->center_id !== $centerId) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Quiz not found.',
                ],
            ], 404);
        }

        // Verify student is enrolled
        $enrollment = $student->enrollments()
            ->where('course_id', $quiz->course_id)
            ->where('center_id', $centerId)
            ->active()
            ->first();

        if (! $enrollment) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_ENROLLED',
                    'message' => 'You are not enrolled in this course.',
                ],
            ], 403);
        }

        if (! $quiz->is_active || ! $this->quizService->isAvailable($quiz, $student)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_AVAILABLE',
                    'message' => 'This quiz is not currently available.',
                ],
            ], 400);
        }

        $quiz->setAttribute('remaining_attempts', $this->quizService->getRemainingAttempts($quiz, $student));
        $quiz->setAttribute('best_score', $this->attemptService->calculateFinalScore($quiz, $student));
        $quiz->setAttribute('can_attempt', $this->quizService->canAttempt($quiz, $student));
        $quiz->setAttribute('total_questions', $quiz->questions()->active()->count());
        $quiz->setAttribute('total_points', $quiz->questions()->active()->sum('points'));

        return response()->json([
            'success' => true,
            'message' => 'Operation completed',
            'data' => new QuizInfoResource($quiz),
        ]);
    }

    /**
     * Get student's attempt history for a quiz.
     */
    public function myAttempts(int $centerId, Quiz $quiz): JsonResponse
    {
        /** @var User|null $student */
        $student = request()->user();

        if (! $student instanceof User || $student->is_student === false) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Only students can access quizzes.',
                ],
            ], 403);
        }

        if ($quiz->center_id !== $centerId) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Quiz not found.',
                ],
            ], 404);
        }

        $attempts = $this->attemptService->getAttempts($quiz, $student);
        $totalQuestions = $quiz->questions()->active()->count();
        $gradedAttempts = $attempts->filter(static fn (QuizAttempt $attempt): bool => is_numeric($attempt->score));
        $completedAttempts = $attempts->filter(static fn (QuizAttempt $attempt): bool => $attempt->status->isCompleted());
        $failedCompletedAttempts = $completedAttempts->filter(static fn (QuizAttempt $attempt): bool => $attempt->passed === false);

        return response()->json([
            'success' => true,
            'message' => 'Operation completed',
            'data' => [
                'attempts' => $attempts->map(fn ($attempt): array => [
                    'id' => $attempt->id,
                    'attempt_number' => $attempt->attempt_number,
                    'status' => $attempt->status->value,
                    'status_label' => $attempt->status->label(),
                    'score' => $attempt->score !== null ? (float) $attempt->score : null,
                    'passed' => $attempt->passed,
                    'started_at' => $attempt->started_at?->toIso8601String(),
                    'submitted_at' => $attempt->submitted_at?->toIso8601String(),
                    'time_spent_seconds' => $attempt->time_spent_seconds,
                    'answered_questions' => (int) ($attempt->answers_count ?? 0),
                    'total_questions' => $totalQuestions,
                    'can_resume' => $attempt->isInProgress(),
                ]),
                'stats' => [
                    'lowest_score' => $gradedAttempts->isEmpty() ? null : (float) $gradedAttempts->min('score'),
                    'average_score' => $gradedAttempts->isEmpty() ? null : round((float) $gradedAttempts->avg('score'), 2),
                    'highest_score' => $gradedAttempts->isEmpty() ? null : (float) $gradedAttempts->max('score'),
                    'opened_count' => $attempts->count(),
                    'completed_count' => $completedAttempts->count(),
                    'failed_count' => $failedCompletedAttempts->count(),
                ],
                'best_score' => $this->attemptService->calculateFinalScore($quiz, $student),
                'remaining_attempts' => $this->quizService->getRemainingAttempts($quiz, $student),
            ],
        ]);
    }
}
