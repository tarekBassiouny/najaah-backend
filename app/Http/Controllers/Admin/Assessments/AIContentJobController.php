<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Assessments;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AIContent\CreateAIContentJobRequest;
use App\Http\Requests\Admin\AIContent\ListAIContentJobsRequest;
use App\Http\Requests\Admin\AIContent\ReviewAIContentJobRequest;
use App\Http\Resources\Admin\AIContent\AIContentJobResource;
use App\Jobs\ProcessAIContentJob;
use App\Models\AIContentJob;
use App\Models\Center;
use App\Models\User;
use App\Services\Assessments\Contracts\AIContentServiceInterface;
use Illuminate\Http\JsonResponse;

class AIContentJobController extends Controller
{
    public function __construct(
        private readonly AIContentServiceInterface $aiContentService
    ) {}

    /**
     * List AI content jobs.
     *
     * @group Admin - AI Content
     */
    public function index(ListAIContentJobsRequest $request, Center $center): JsonResponse
    {
        $query = AIContentJob::query()
            ->where('center_id', $center->id)
            ->latest('id');

        if ($request->filled('course_id')) {
            $query->where('course_id', $request->integer('course_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->integer('status'));
        }

        if ($request->filled('target_type')) {
            $query->where('target_type', $request->string('target_type')->toString());
        }

        $jobs = $query->paginate($request->integer('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => AIContentJobResource::collection($jobs),
            'meta' => [
                'current_page' => $jobs->currentPage(),
                'per_page' => $jobs->perPage(),
                'total' => $jobs->total(),
                'last_page' => $jobs->lastPage(),
            ],
        ]);
    }

    /**
     * Create and enqueue a new AI content generation job.
     *
     * @group Admin - AI Content
     */
    public function store(CreateAIContentJobRequest $request, Center $center): JsonResponse
    {
        /** @var User|null $admin */
        $admin = $request->user();
        if (! $admin instanceof User) {
            abort(401, 'Unauthorized');
        }

        $job = $this->aiContentService->createJob($center, $request->validated(), $admin);

        ProcessAIContentJob::dispatch($job);

        return response()->json([
            'success' => true,
            'message' => 'AI content generation job queued successfully.',
            'data' => new AIContentJobResource($job),
        ], 202);
    }

    /**
     * Show AI content job details.
     *
     * @group Admin - AI Content
     */
    public function show(Center $center, AIContentJob $job): JsonResponse
    {
        $this->assertJobInCenter($job, $center);

        return response()->json([
            'success' => true,
            'data' => new AIContentJobResource($job),
        ]);
    }

    /**
     * Save reviewed payload for the job.
     *
     * @group Admin - AI Content
     */
    public function review(ReviewAIContentJobRequest $request, Center $center, AIContentJob $job): JsonResponse
    {
        $this->assertJobInCenter($job, $center);
        /** @var User|null $admin */
        $admin = $request->user();
        if (! $admin instanceof User) {
            abort(401, 'Unauthorized');
        }

        $updated = $this->aiContentService->reviewJob(
            $job,
            $request->validated('reviewed_payload'),
            $admin
        );

        return response()->json([
            'success' => true,
            'message' => 'AI content job reviewed successfully.',
            'data' => new AIContentJobResource($updated),
        ]);
    }

    /**
     * Approve a completed job.
     *
     * @group Admin - AI Content
     */
    public function approve(Center $center, AIContentJob $job): JsonResponse
    {
        $this->assertJobInCenter($job, $center);
        /** @var User|null $admin */
        $admin = request()->user();
        if (! $admin instanceof User) {
            abort(401, 'Unauthorized');
        }

        $updated = $this->aiContentService->approveJob($job, $admin);

        return response()->json([
            'success' => true,
            'message' => 'AI content job approved successfully.',
            'data' => new AIContentJobResource($updated),
        ]);
    }

    /**
     * Publish an approved job into LMS entities.
     *
     * @group Admin - AI Content
     */
    public function publish(Center $center, AIContentJob $job): JsonResponse
    {
        $this->assertJobInCenter($job, $center);
        /** @var User|null $admin */
        $admin = request()->user();
        if (! $admin instanceof User) {
            abort(401, 'Unauthorized');
        }

        $result = $this->aiContentService->publishJob($job, $admin);

        return response()->json([
            'success' => true,
            'message' => 'AI content job published successfully.',
            'data' => [
                'job' => new AIContentJobResource($job->fresh() ?? $job),
                'publication' => $result,
            ],
        ]);
    }

    /**
     * Discard a job.
     *
     * @group Admin - AI Content
     */
    public function destroy(Center $center, AIContentJob $job): JsonResponse
    {
        $this->assertJobInCenter($job, $center);

        $this->aiContentService->discardJob($job);

        return response()->json([
            'success' => true,
            'message' => 'AI content job discarded successfully.',
        ]);
    }

    private function assertJobInCenter(AIContentJob $job, Center $center): void
    {
        if ($job->center_id !== $center->id) {
            abort(404, 'AI content job not found in this center.');
        }
    }
}
