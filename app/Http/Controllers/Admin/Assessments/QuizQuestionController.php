<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Assessments;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Quiz\ReorderQuestionsRequest;
use App\Http\Requests\Admin\Quiz\StoreQuestionRequest;
use App\Http\Requests\Admin\Quiz\UpdateQuestionRequest;
use App\Http\Resources\Admin\Quiz\QuizQuestionResource;
use App\Models\Center;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Services\Assessments\Contracts\QuizServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuizQuestionController extends Controller
{
    public function __construct(
        private readonly QuizServiceInterface $quizService
    ) {}

    /**
     * List questions for a quiz.
     *
     * @group Admin - Quiz Questions
     */
    public function index(Request $request, Center $center, Quiz $quiz): JsonResponse
    {
        $this->assertQuizInCenter($quiz, $center);

        $quiz->load(['questions' => function ($query): void {
            $query->orderBy('order_index');
        }, 'questions.answers']);

        return response()->json([
            'success' => true,
            'data' => QuizQuestionResource::collection($quiz->questions),
        ]);
    }

    /**
     * Store a new question.
     *
     * @group Admin - Quiz Questions
     */
    public function store(StoreQuestionRequest $request, Center $center, Quiz $quiz): JsonResponse
    {
        $this->assertQuizInCenter($quiz, $center);

        $question = $this->quizService->addQuestion($quiz, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Question added successfully.',
            'data' => new QuizQuestionResource($question),
        ], 201);
    }

    /**
     * Update a question.
     *
     * @group Admin - Quiz Questions
     */
    public function update(
        UpdateQuestionRequest $request,
        Center $center,
        Quiz $quiz,
        QuizQuestion $question
    ): JsonResponse {
        $this->assertQuizInCenter($quiz, $center);
        $this->assertQuestionInQuiz($question, $quiz);

        $question = $this->quizService->updateQuestion($question, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Question updated successfully.',
            'data' => new QuizQuestionResource($question),
        ]);
    }

    /**
     * Delete a question.
     *
     * @group Admin - Quiz Questions
     */
    public function destroy(Request $request, Center $center, Quiz $quiz, QuizQuestion $question): JsonResponse
    {
        $this->assertQuizInCenter($quiz, $center);
        $this->assertQuestionInQuiz($question, $quiz);

        $this->quizService->deleteQuestion($question);

        return response()->json([
            'success' => true,
            'message' => 'Question deleted successfully.',
        ]);
    }

    /**
     * Reorder questions in a quiz.
     *
     * @group Admin - Quiz Questions
     */
    public function reorder(ReorderQuestionsRequest $request, Center $center, Quiz $quiz): JsonResponse
    {
        $this->assertQuizInCenter($quiz, $center);

        $this->quizService->reorderQuestions($quiz, $request->validated('question_ids'));

        return response()->json([
            'success' => true,
            'message' => 'Questions reordered successfully.',
        ]);
    }

    private function assertQuizInCenter(Quiz $quiz, Center $center): void
    {
        if ($quiz->center_id !== $center->id) {
            abort(404, 'Quiz not found in this center.');
        }
    }

    private function assertQuestionInQuiz(QuizQuestion $question, Quiz $quiz): void
    {
        if ($question->quiz_id !== $quiz->id) {
            abort(404, 'Question not found in this quiz.');
        }
    }
}
