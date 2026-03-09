<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Assessments;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Quiz\ListQuizzesRequest;
use App\Http\Requests\Admin\Quiz\StoreQuizRequest;
use App\Http\Requests\Admin\Quiz\UpdateQuizRequest;
use App\Http\Resources\Admin\Quiz\QuizDetailResource;
use App\Http\Resources\Admin\Quiz\QuizResource;
use App\Models\Center;
use App\Models\Course;
use App\Models\Quiz;
use App\Services\Assessments\Contracts\QuizServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuizController extends Controller
{
    public function __construct(
        private readonly QuizServiceInterface $quizService
    ) {}

    /**
     * List quizzes for a course.
     *
     * @group Admin - Quizzes
     */
    public function index(ListQuizzesRequest $request, Center $center, Course $course): JsonResponse
    {
        $this->assertCourseInCenter($course, $center);

        $quizzes = $this->quizService->getQuizzesForCourse(
            $course,
            $request->boolean('active_only', false)
        );

        return response()->json([
            'success' => true,
            'data' => QuizResource::collection($quizzes),
        ]);
    }

    /**
     * Store a new quiz.
     *
     * @group Admin - Quizzes
     */
    public function store(StoreQuizRequest $request, Center $center, Course $course): JsonResponse
    {
        $this->assertCourseInCenter($course, $center);

        $admin = $request->user();
        $quiz = $this->quizService->create($course, $request->validated(), $admin);

        return response()->json([
            'success' => true,
            'message' => 'Quiz created successfully.',
            'data' => new QuizDetailResource($quiz),
        ], 201);
    }

    /**
     * Show quiz details.
     *
     * @group Admin - Quizzes
     */
    public function show(Request $request, Center $center, Quiz $quiz): JsonResponse
    {
        $this->assertQuizInCenter($quiz, $center);

        $quiz = $this->quizService->getQuizWithQuestions($quiz);

        return response()->json([
            'success' => true,
            'data' => new QuizDetailResource($quiz),
        ]);
    }

    /**
     * Update a quiz.
     *
     * @group Admin - Quizzes
     */
    public function update(UpdateQuizRequest $request, Center $center, Quiz $quiz): JsonResponse
    {
        $this->assertQuizInCenter($quiz, $center);

        $quiz = $this->quizService->update($quiz, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Quiz updated successfully.',
            'data' => new QuizDetailResource($quiz),
        ]);
    }

    /**
     * Delete a quiz.
     *
     * @group Admin - Quizzes
     */
    public function destroy(Request $request, Center $center, Quiz $quiz): JsonResponse
    {
        $this->assertQuizInCenter($quiz, $center);

        $this->quizService->delete($quiz);

        return response()->json([
            'success' => true,
            'message' => 'Quiz deleted successfully.',
        ]);
    }

    /**
     * Duplicate a quiz.
     *
     * @group Admin - Quizzes
     */
    public function duplicate(Request $request, Center $center, Quiz $quiz): JsonResponse
    {
        $this->assertQuizInCenter($quiz, $center);

        $admin = $request->user();
        $newQuiz = $this->quizService->duplicate($quiz, $admin);

        return response()->json([
            'success' => true,
            'message' => 'Quiz duplicated successfully.',
            'data' => new QuizDetailResource($newQuiz),
        ], 201);
    }

    private function assertCourseInCenter(Course $course, Center $center): void
    {
        if ($course->center_id !== $center->id) {
            abort(404, 'Course not found in this center.');
        }
    }

    private function assertQuizInCenter(Quiz $quiz, Center $center): void
    {
        if ($quiz->center_id !== $center->id) {
            abort(404, 'Quiz not found in this center.');
        }
    }
}
