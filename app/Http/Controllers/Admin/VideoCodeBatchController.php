<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\VideoCodeBatchStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\VideoAccess\CloseVideoCodeBatchRequest;
use App\Http\Requests\Admin\VideoAccess\CreateVideoCodeBatchRequest;
use App\Http\Requests\Admin\VideoAccess\ExpandVideoCodeBatchRequest;
use App\Http\Requests\Admin\VideoAccess\ExportVideoCodeBatchRequest;
use App\Http\Requests\Admin\VideoAccess\ListVideoCodeBatchesRequest;
use App\Http\Requests\Admin\VideoAccess\ListVideoCodeBatchRedemptionsRequest;
use App\Http\Requests\Admin\VideoAccess\SendVideoCodeBatchCsvWhatsAppRequest;
use App\Http\Resources\Admin\VideoAccess\VideoCodeBatchListResource;
use App\Http\Resources\Admin\VideoAccess\VideoCodeBatchRedemptionResource;
use App\Http\Resources\Admin\VideoAccess\VideoCodeBatchResource;
use App\Http\Resources\Admin\VideoAccess\VideoCodeBatchStatisticsResource;
use App\Models\Center;
use App\Models\Course;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoCodeBatch;
use App\Services\Settings\PolicySettingsService;
use App\Services\VideoAccess\Contracts\VideoCodeBatchServiceInterface;
use App\Support\ErrorCodes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request as HttpRequest;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VideoCodeBatchController extends Controller
{
    public function __construct(
        private readonly VideoCodeBatchServiceInterface $batchService,
        private readonly PolicySettingsService $policySettingsService
    ) {}

    public function index(ListVideoCodeBatchesRequest $request, Center $center): JsonResponse
    {
        $this->requireAdmin($request);

        $query = VideoCodeBatch::query()
            ->where('center_id', $center->id)
            ->with(['video', 'course', 'generator', 'closer'])
            ->orderByDesc('created_at');

        if ($request->filled('video_id')) {
            $query->where('video_id', (int) $request->input('video_id'));
        }

        if ($request->filled('course_id')) {
            $query->where('course_id', (int) $request->input('course_id'));
        }

        if ($request->filled('status')) {
            $status = $request->input('status') === 'open'
                ? VideoCodeBatchStatus::Open
                : VideoCodeBatchStatus::Closed;
            $query->where('status', $status->value);
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));

            if ($search !== '') {
                $query->where(function (Builder $builder) use ($search): void {
                    $builder
                        ->where('batch_code', 'like', '%'.$search.'%')
                        ->orWhereHas('video', function (Builder $videoQuery) use ($search): void {
                            $videoQuery->whereTranslationLike(['title'], $search, ['en', 'ar']);
                        })
                        ->orWhereHas('course', function (Builder $courseQuery) use ($search): void {
                            $courseQuery->whereTranslationLike(['title'], $search, ['en', 'ar']);
                        });
                });
            }
        }

        $perPage = (int) $request->input('per_page', 20);
        $paginator = $query->paginate($perPage);

        $policy = $this->policySettingsService->resolveCenterPolicy($center);
        $catalog = $this->policySettingsService->catalog();

        return response()->json([
            'success' => true,
            'message' => 'Video code batches retrieved successfully',
            'data' => VideoCodeBatchListResource::collection($paginator->items()),
            'meta' => [
                'page' => $paginator->currentPage(),
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
            'settings' => [
                'max_quantity' => (int) ($policy['video_code_batch_max_quantity'] ?? $catalog['video_code_batch_max_quantity']['default']),
                'default_view_limit' => (int) ($policy['video_code_batch_default_view_limit'] ?? $catalog['video_code_batch_default_view_limit']['default']),
            ],
        ]);
    }

    public function store(
        CreateVideoCodeBatchRequest $request,
        Center $center,
        Course $course,
        Video $video
    ): JsonResponse {
        $admin = $this->requireAdmin($request);

        $this->assertCourseBelongsToCenter($course, $center);
        $this->assertVideoInCenter($video, $center);
        $this->assertVideoInCourse($video, $course);

        /** @var array{quantity:int,view_limit_per_code?:int} $data */
        $data = $request->validated();

        $policy = $this->policySettingsService->resolveCenterPolicy($center);
        $catalog = $this->policySettingsService->catalog();
        $defaultViewLimit = (int) ($policy['video_code_batch_default_view_limit'] ?? $catalog['video_code_batch_default_view_limit']['default']);

        $batch = $this->batchService->createBatch(
            $admin,
            $video,
            $course,
            (int) $data['quantity'],
            (int) ($data['view_limit_per_code'] ?? $defaultViewLimit)
        );

        return response()->json([
            'success' => true,
            'message' => 'Video code batch created successfully',
            'data' => new VideoCodeBatchResource($batch),
        ], 201);
    }

    public function show(HttpRequest $request, Center $center, VideoCodeBatch $batch): JsonResponse
    {
        $this->requireAdmin($request);
        $this->assertBatchBelongsToCenter($center, $batch);

        $batch->load(['video', 'course', 'generator', 'closer']);

        return response()->json([
            'success' => true,
            'data' => new VideoCodeBatchResource($batch),
        ]);
    }

    public function expand(
        ExpandVideoCodeBatchRequest $request,
        Center $center,
        VideoCodeBatch $batch
    ): JsonResponse {
        $admin = $this->requireAdmin($request);
        $this->assertBatchBelongsToCenter($center, $batch);

        /** @var array{additional_quantity:int} $data */
        $data = $request->validated();

        $batch = $this->batchService->expandBatch(
            $admin,
            $batch,
            (int) $data['additional_quantity']
        );

        return response()->json([
            'success' => true,
            'message' => 'Video code batch expanded successfully',
            'data' => new VideoCodeBatchResource($batch),
        ]);
    }

    public function close(
        CloseVideoCodeBatchRequest $request,
        Center $center,
        VideoCodeBatch $batch
    ): JsonResponse {
        $admin = $this->requireAdmin($request);
        $this->assertBatchBelongsToCenter($center, $batch);
        $wasClosed = $batch->isClosed();

        /** @var array{sold_limit:int} $data */
        $data = $request->validated();

        $batch = $this->batchService->closeBatch(
            $admin,
            $batch,
            (int) $data['sold_limit']
        );

        return response()->json([
            'success' => true,
            'message' => $wasClosed
                ? 'Video code batch sold limit updated successfully'
                : 'Video code batch closed successfully',
            'data' => new VideoCodeBatchResource($batch),
        ]);
    }

    public function statistics(
        HttpRequest $request,
        Center $center,
        VideoCodeBatch $batch
    ): JsonResponse {
        $this->requireAdmin($request);
        $this->assertBatchBelongsToCenter($center, $batch);

        $stats = $this->batchService->getStatistics($batch);

        return response()->json([
            'success' => true,
            'data' => new VideoCodeBatchStatisticsResource($stats),
        ]);
    }

    public function redemptions(
        ListVideoCodeBatchRedemptionsRequest $request,
        Center $center,
        VideoCodeBatch $batch
    ): JsonResponse {
        $this->requireAdmin($request);
        $this->assertBatchBelongsToCenter($center, $batch);

        $paginator = $this->batchService->getRedemptions(
            $batch,
            (int) $request->input('per_page', 15),
            (int) $request->input('page', 1),
            $request->filled('search') ? trim((string) $request->input('search')) : null
        );

        return response()->json([
            'success' => true,
            'data' => VideoCodeBatchRedemptionResource::collection($paginator->items()),
            'meta' => [
                'page' => $paginator->currentPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function exportCsv(
        ExportVideoCodeBatchRequest $request,
        Center $center,
        VideoCodeBatch $batch
    ): StreamedResponse {
        $admin = $this->requireAdmin($request);
        $this->assertBatchBelongsToCenter($center, $batch);

        $startSequence = $request->filled('start_sequence') ? (int) $request->input('start_sequence') : null;
        $endSequence = $request->filled('end_sequence') ? (int) $request->input('end_sequence') : null;

        return $this->batchService->exportAsCsv($admin, $batch, $startSequence, $endSequence);
    }

    public function exportPdf(
        ExportVideoCodeBatchRequest $request,
        Center $center,
        VideoCodeBatch $batch
    ): StreamedResponse {
        $admin = $this->requireAdmin($request);
        $this->assertBatchBelongsToCenter($center, $batch);

        $startSequence = $request->filled('start_sequence') ? (int) $request->input('start_sequence') : null;
        $endSequence = $request->filled('end_sequence') ? (int) $request->input('end_sequence') : null;
        $cardsPerPage = $request->filled('cards_per_page') ? (int) $request->input('cards_per_page') : 8;

        return $this->batchService->exportAsPdf($admin, $batch, $startSequence, $endSequence, $cardsPerPage);
    }

    public function sendWhatsAppCsv(
        SendVideoCodeBatchCsvWhatsAppRequest $request,
        Center $center,
        VideoCodeBatch $batch
    ): JsonResponse {
        $admin = $this->requireAdmin($request);
        $this->assertBatchBelongsToCenter($center, $batch);

        /** @var array{phone_number:string,start_sequence?:int,end_sequence?:int} $data */
        $data = $request->validated();

        $record = $this->batchService->queueCsvWhatsAppSend(
            $admin,
            $batch,
            (string) $data['phone_number'],
            isset($data['start_sequence']) ? $data['start_sequence'] : null,
            isset($data['end_sequence']) ? $data['end_sequence'] : null
        );

        if (($record['status'] ?? null) === 'failed') {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => ErrorCodes::WHATSAPP_SEND_FAILED,
                    'message' => (string) ($record['error'] ?? 'Failed to send WhatsApp CSV export.'),
                ],
                'message' => (string) ($record['error'] ?? 'Failed to send WhatsApp CSV export.'),
                'code' => ErrorCodes::WHATSAPP_SEND_FAILED,
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => ($record['status'] ?? null) === 'sent'
                ? 'WhatsApp CSV sent successfully.'
                : 'WhatsApp CSV send started.',
            'data' => $record,
        ]);
    }

    private function requireAdmin(HttpRequest $request): User
    {
        /** @var User|null $admin */
        $admin = $request->user();

        if (! $admin instanceof User) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Authentication required.',
                ],
            ], 401));
        }

        return $admin;
    }

    private function assertBatchBelongsToCenter(Center $center, VideoCodeBatch $batch): void
    {
        if ((int) $batch->center_id !== (int) $center->id) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Video code batch not found.',
                ],
            ], 404));
        }
    }

    private function assertVideoInCenter(Video $video, Center $center): void
    {
        if ((int) $video->center_id !== (int) $center->id) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Video not found.',
                ],
            ], 404));
        }
    }

    private function assertCourseBelongsToCenter(Course $course, Center $center): void
    {
        if ((int) $course->center_id !== (int) $center->id) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Course not found.',
                ],
            ], 404));
        }
    }

    private function assertVideoInCourse(Video $video, Course $course): void
    {
        $videoExistsInCourse = $course->videos()
            ->whereKey($video->id)
            ->exists();

        if (! $videoExistsInCourse) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Video not found in this course.',
                ],
            ], 404));
        }
    }
}
