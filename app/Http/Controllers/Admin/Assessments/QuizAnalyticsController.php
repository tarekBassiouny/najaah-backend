<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Assessments;

use App\Enums\QuizAttemptStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Quiz\ListAttemptsRequest;
use App\Http\Resources\Admin\Quiz\QuizAttemptResource;
use App\Http\Resources\Admin\Quiz\QuizAttemptDetailResource;
use App\Models\Center;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Services\Assessments\Contracts\QuizAttemptServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuizAnalyticsController extends Controller
{
    public function __construct(
        private readonly QuizAttemptServiceInterface $attemptService
    ) {}

    /**
     * List all attempts for a quiz.
     *
     * @group Admin - Quiz Analytics
     */
    public function attempts(ListAttemptsRequest $request, Center $center, Quiz $quiz): JsonResponse
    {
        $this->assertQuizInCenter($quiz, $center);

        $query = $quiz->attempts()
            ->with(['user'])
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter by user
        if ($request->has('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        // Filter by passed
        if ($request->has('passed')) {
            $query->where('passed', $request->boolean('passed'));
        }

        $perPage = $request->integer('per_page', 15);
        $attempts = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => QuizAttemptResource::collection($attempts->items()),
            'meta' => [
                'page' => $attempts->currentPage(),
                'per_page' => $attempts->perPage(),
                'total' => $attempts->total(),
                'last_page' => $attempts->lastPage(),
            ],
        ]);
    }

    /**
     * Get quiz statistics and analytics.
     *
     * @group Admin - Quiz Analytics
     */
    public function analytics(Request $request, Center $center, Quiz $quiz): JsonResponse
    {
        $this->assertQuizInCenter($quiz, $center);

        $totalAttempts = $quiz->attempts()->count();
        $completedAttempts = $quiz->attempts()
            ->where('status', QuizAttemptStatus::Graded)
            ->count();
        $passedAttempts = $quiz->attempts()
            ->where('passed', true)
            ->count();

        $averageScore = $quiz->attempts()
            ->where('status', QuizAttemptStatus::Graded)
            ->avg('score');

        $averageTime = $quiz->attempts()
            ->where('status', QuizAttemptStatus::Graded)
            ->avg('time_spent_seconds');

        $uniqueStudents = $quiz->attempts()
            ->distinct('user_id')
            ->count('user_id');

        $passRate = $completedAttempts > 0
            ? round(($passedAttempts / $completedAttempts) * 100, 2)
            : 0;

        // Score distribution
        $scoreDistribution = $this->getScoreDistribution($quiz);

        // Question analytics
        $questionAnalytics = $this->getQuestionAnalytics($quiz);

        return response()->json([
            'success' => true,
            'data' => [
                'quiz_id' => $quiz->id,
                'summary' => [
                    'total_attempts' => $totalAttempts,
                    'completed_attempts' => $completedAttempts,
                    'passed_attempts' => $passedAttempts,
                    'unique_students' => $uniqueStudents,
                    'pass_rate' => $passRate,
                    'average_score' => $averageScore !== null ? round((float) $averageScore, 2) : null,
                    'average_time_seconds' => $averageTime !== null ? (int) $averageTime : null,
                ],
                'score_distribution' => $scoreDistribution,
                'question_analytics' => $questionAnalytics,
            ],
        ]);
    }

    /**
     * Get detailed attempt information.
     *
     * @group Admin - Quiz Analytics
     */
    public function attemptDetail(Request $request, Center $center, Quiz $quiz, QuizAttempt $attempt): JsonResponse
    {
        $this->assertQuizInCenter($quiz, $center);
        $this->assertAttemptInQuiz($attempt, $quiz);

        $details = $this->attemptService->getAttemptDetails($attempt);

        return response()->json([
            'success' => true,
            'data' => $details,
        ]);
    }

    /**
     * @return array<string, int>
     */
    private function getScoreDistribution(Quiz $quiz): array
    {
        $ranges = [
            '0-20' => 0,
            '21-40' => 0,
            '41-60' => 0,
            '61-80' => 0,
            '81-100' => 0,
        ];

        $attempts = $quiz->attempts()
            ->where('status', QuizAttemptStatus::Graded)
            ->get();

        foreach ($attempts as $attempt) {
            $score = (float) $attempt->score;
            if ($score <= 20) {
                $ranges['0-20']++;
            } elseif ($score <= 40) {
                $ranges['21-40']++;
            } elseif ($score <= 60) {
                $ranges['41-60']++;
            } elseif ($score <= 80) {
                $ranges['61-80']++;
            } else {
                $ranges['81-100']++;
            }
        }

        return $ranges;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getQuestionAnalytics(Quiz $quiz): array
    {
        $quiz->load(['questions' => function ($query): void {
            $query->where('is_active', true);
        }]);

        $analytics = [];

        foreach ($quiz->questions as $question) {
            $totalAnswers = $question->attemptAnswers()->count();
            $correctAnswers = $question->attemptAnswers()
                ->where('is_correct', true)
                ->count();

            $correctRate = $totalAnswers > 0
                ? round(($correctAnswers / $totalAnswers) * 100, 2)
                : 0;

            $analytics[] = [
                'question_id' => $question->id,
                'question' => $question->translate('question'),
                'total_answers' => $totalAnswers,
                'correct_answers' => $correctAnswers,
                'correct_rate' => $correctRate,
                'points' => (float) $question->points,
            ];
        }

        return $analytics;
    }

    private function assertQuizInCenter(Quiz $quiz, Center $center): void
    {
        if ($quiz->center_id !== $center->id) {
            abort(404, 'Quiz not found in this center.');
        }
    }

    private function assertAttemptInQuiz(QuizAttempt $attempt, Quiz $quiz): void
    {
        if ($attempt->quiz_id !== $quiz->id) {
            abort(404, 'Attempt not found in this quiz.');
        }
    }
}
