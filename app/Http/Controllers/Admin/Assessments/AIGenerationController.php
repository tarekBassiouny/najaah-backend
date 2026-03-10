<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Assessments;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Quiz\ApproveQuestionsRequest;
use App\Http\Requests\Admin\Quiz\GenerateFromPdfRequest;
use App\Http\Requests\Admin\Quiz\GenerateFromVideoRequest;
use App\Http\Resources\Admin\Quiz\AIGenerationJobResource;
use App\Jobs\ProcessAIGenerationJob;
use App\Models\AIGenerationJob;
use App\Models\Center;
use App\Models\Pdf;
use App\Models\Quiz;
use App\Models\Video;
use App\Services\Assessments\Contracts\AIQuizGeneratorServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AIGenerationController extends Controller
{
    public function __construct(
        private readonly AIQuizGeneratorServiceInterface $aiGeneratorService
    ) {}

    /**
     * Generate quiz questions from a video transcript.
     *
     * @group Admin - AI Generation
     */
    public function generateFromVideo(GenerateFromVideoRequest $request, Center $center, Quiz $quiz): JsonResponse
    {
        $this->assertQuizInCenter($quiz, $center);

        $video = Video::query()
            ->whereKey($request->integer('video_id'))
            ->firstOrFail();
        $admin = $request->user();

        $job = $this->aiGeneratorService->generateFromVideo(
            $quiz,
            $video,
            $request->validated('question_count', 5),
            $admin
        );

        // Dispatch job for async processing
        ProcessAIGenerationJob::dispatch($job);

        return response()->json([
            'success' => true,
            'message' => 'AI generation job queued successfully.',
            'data' => new AIGenerationJobResource($job),
        ], 202);
    }

    /**
     * Generate quiz questions from a PDF document.
     *
     * @group Admin - AI Generation
     */
    public function generateFromPdf(GenerateFromPdfRequest $request, Center $center, Quiz $quiz): JsonResponse
    {
        $this->assertQuizInCenter($quiz, $center);

        $pdf = Pdf::query()
            ->whereKey($request->integer('pdf_id'))
            ->firstOrFail();
        $admin = $request->user();

        $job = $this->aiGeneratorService->generateFromPdf(
            $quiz,
            $pdf,
            $request->validated('question_count', 5),
            $admin
        );

        // Dispatch job for async processing
        ProcessAIGenerationJob::dispatch($job);

        return response()->json([
            'success' => true,
            'message' => 'AI generation job queued successfully.',
            'data' => new AIGenerationJobResource($job),
        ], 202);
    }

    /**
     * Get AI generation job status.
     *
     * @group Admin - AI Generation
     */
    public function show(Request $request, Center $center, AIGenerationJob $aiGenerationJob): JsonResponse
    {
        $this->assertJobInCenter($aiGenerationJob, $center);

        $status = $this->aiGeneratorService->getJobStatus($aiGenerationJob);

        return response()->json([
            'success' => true,
            'data' => $status,
        ]);
    }

    /**
     * Approve generated questions and add them to the quiz.
     *
     * @group Admin - AI Generation
     */
    public function approve(ApproveQuestionsRequest $request, Center $center, AIGenerationJob $aiGenerationJob): JsonResponse
    {
        $this->assertJobInCenter($aiGenerationJob, $center);

        if (! $aiGenerationJob->isCompleted()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'JOB_NOT_COMPLETED',
                    'message' => 'Cannot approve questions from an incomplete job.',
                ],
            ], 422);
        }

        $questionIndexes = $request->validated('question_indexes');
        $addedCount = $this->aiGeneratorService->approveQuestions($aiGenerationJob, $questionIndexes);

        return response()->json([
            'success' => true,
            'message' => $addedCount.' questions added to quiz.',
            'data' => [
                'questions_added' => $addedCount,
            ],
        ]);
    }

    /**
     * Discard generated questions.
     *
     * @group Admin - AI Generation
     */
    public function discard(Request $request, Center $center, AIGenerationJob $aiGenerationJob): JsonResponse
    {
        $this->assertJobInCenter($aiGenerationJob, $center);

        $this->aiGeneratorService->discardQuestions($aiGenerationJob);

        return response()->json([
            'success' => true,
            'message' => 'Generated questions discarded.',
        ]);
    }

    private function assertQuizInCenter(Quiz $quiz, Center $center): void
    {
        if ($quiz->center_id !== $center->id) {
            abort(404, 'Quiz not found in this center.');
        }
    }

    private function assertJobInCenter(AIGenerationJob $job, Center $center): void
    {
        if ($job->quiz->center_id !== $center->id) {
            abort(404, 'AI generation job not found in this center.');
        }
    }
}
