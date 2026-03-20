<?php

declare(strict_types=1);

namespace App\Http\Controllers\Mobile;

use App\Enums\QuizAttemptStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Mobile\Quiz\SaveAnswerRequest;
use App\Http\Requests\Mobile\Quiz\StartQuizRequest;
use App\Http\Resources\Mobile\Quiz\QuizAttemptDetailResource;
use App\Http\Resources\Mobile\Quiz\QuizResultResource;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\User;
use App\Services\Assessments\Contracts\QuizAttemptServiceInterface;
use App\Services\Assessments\Contracts\QuizServiceInterface;
use Illuminate\Http\JsonResponse;

class QuizAttemptController extends Controller
{
    public function __construct(
        private readonly QuizServiceInterface $quizService,
        private readonly QuizAttemptServiceInterface $attemptService
    ) {}

    /**
     * Start a new quiz attempt.
     */
    public function start(StartQuizRequest $request, int $centerId, Quiz $quiz): JsonResponse
    {
        /** @var User $student */
        $student = $request->user();

        if ($quiz->center_id !== $centerId) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Quiz not found.',
                ],
            ], 404);
        }

        // Verify enrollment
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

        // Check if quiz is available
        if (! $quiz->is_active || ! $this->quizService->isAvailable($quiz, $student)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_AVAILABLE',
                    'message' => 'This quiz is not currently available.',
                ],
            ], 400);
        }

        // Check if student can attempt
        if (! $this->quizService->canAttempt($quiz, $student)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NO_ATTEMPTS_LEFT',
                    'message' => 'You have no remaining attempts for this quiz.',
                ],
            ], 400);
        }

        // Check for existing in-progress attempt
        $existingAttempt = $quiz->attempts()
            ->where('user_id', $student->id)
            ->where('status', QuizAttemptStatus::InProgress)
            ->first();

        if ($existingAttempt) {
            return response()->json([
                'success' => true,
                'message' => 'Resuming existing attempt',
                'data' => new QuizAttemptDetailResource($existingAttempt->load(['quiz.questions.answers', 'answers'])),
            ]);
        }

        try {
            $attempt = $this->attemptService->start($quiz, $student, $enrollment);
            $attempt->load(['quiz.questions.answers', 'answers']);

            return response()->json([
                'success' => true,
                'message' => 'Quiz attempt started',
                'data' => new QuizAttemptDetailResource($attempt),
            ], 201);
        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'START_FAILED',
                    'message' => 'Failed to start quiz attempt.',
                ],
            ], 500);
        }
    }

    /**
     * Get current attempt details.
     */
    public function show(int $centerId, QuizAttempt $attempt): JsonResponse
    {
        /** @var User|null $student */
        $student = request()->user();

        if (! $student instanceof User || $student->is_student === false) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Only students can access quiz attempts.',
                ],
            ], 403);
        }

        if ($attempt->user_id !== $student->id || $attempt->center_id !== $centerId) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Attempt not found.',
                ],
            ], 404);
        }

        $attempt->load(['quiz.questions.answers', 'answers']);

        return response()->json([
            'success' => true,
            'message' => 'Operation completed',
            'data' => new QuizAttemptDetailResource($attempt),
        ]);
    }

    /**
     * Save an answer during the attempt.
     */
    public function answer(SaveAnswerRequest $request, int $centerId, QuizAttempt $attempt): JsonResponse
    {
        /** @var User $student */
        $student = $request->user();

        if ($attempt->user_id !== $student->id || $attempt->center_id !== $centerId) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Attempt not found.',
                ],
            ], 404);
        }

        if ($attempt->status !== QuizAttemptStatus::InProgress) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'ATTEMPT_CLOSED',
                    'message' => 'This attempt is no longer in progress.',
                ],
            ], 400);
        }

        // Check time limit
        if ($attempt->quiz->time_limit_minutes && $attempt->hasTimeLimitExpired()) {
            // Auto-submit the attempt
            $this->attemptService->submit($attempt);

            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'TIME_EXPIRED',
                    'message' => 'Time limit exceeded. Your attempt has been submitted.',
                ],
            ], 400);
        }

        /** @var array{question_id: int, answer_ids: array<int>} $data */
        $data = $request->validated();

        $question = QuizQuestion::find($data['question_id']);
        if (! $question || $question->quiz_id !== $attempt->quiz_id) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_QUESTION',
                    'message' => 'Invalid question for this quiz.',
                ],
            ], 400);
        }

        try {
            $this->attemptService->saveAnswer($attempt, $question, $data['answer_ids']);

            return response()->json([
                'success' => true,
                'message' => 'Answer saved',
                'data' => [
                    'question_id' => $data['question_id'],
                    'answer_ids' => $data['answer_ids'],
                    'remaining_time_seconds' => $attempt->getRemainingTimeSeconds(),
                ],
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'SAVE_FAILED',
                    'message' => 'Failed to save answer.',
                ],
            ], 500);
        }
    }

    /**
     * Submit the quiz attempt.
     */
    public function submit(int $centerId, QuizAttempt $attempt): JsonResponse
    {
        /** @var User|null $student */
        $student = request()->user();

        if (! $student instanceof User || $student->is_student === false) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Only students can submit quiz attempts.',
                ],
            ], 403);
        }

        if ($attempt->user_id !== $student->id || $attempt->center_id !== $centerId) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Attempt not found.',
                ],
            ], 404);
        }

        if ($attempt->status !== QuizAttemptStatus::InProgress) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'ALREADY_SUBMITTED',
                    'message' => 'This attempt has already been submitted.',
                ],
            ], 400);
        }

        try {
            $attempt = $this->attemptService->submit($attempt);
            $attempt->load(['quiz', 'answers.question.answers']);

            return response()->json([
                'success' => true,
                'message' => 'Quiz submitted successfully',
                'data' => new QuizResultResource($attempt),
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'SUBMIT_FAILED',
                    'message' => 'Failed to submit quiz.',
                ],
            ], 500);
        }
    }

    /**
     * Get results of a completed attempt.
     */
    public function results(int $centerId, QuizAttempt $attempt): JsonResponse
    {
        /** @var User|null $student */
        $student = request()->user();

        if (! $student instanceof User || $student->is_student === false) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Only students can view quiz results.',
                ],
            ], 403);
        }

        if ($attempt->user_id !== $student->id || $attempt->center_id !== $centerId) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Attempt not found.',
                ],
            ], 404);
        }

        if ($attempt->status === QuizAttemptStatus::InProgress) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_SUBMITTED',
                    'message' => 'This attempt has not been submitted yet.',
                ],
            ], 400);
        }

        $attempt->load(['quiz', 'answers.question.answers']);

        return response()->json([
            'success' => true,
            'message' => 'Operation completed',
            'data' => new QuizResultResource($attempt),
        ]);
    }
}
