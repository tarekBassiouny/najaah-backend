<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Videos;

use App\Http\Controllers\Concerns\AdminAuthenticates;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Videos\CreateVideoUploadRequest;
use App\Http\Requests\Admin\Videos\ListVideoUploadSessionsRequest;
use App\Http\Requests\Admin\Videos\StoreVideoUploadSessionRequest;
use App\Http\Resources\Admin\Videos\VideoResource;
use App\Http\Resources\Admin\Videos\VideoUploadSessionStatusResource;
use App\Models\Center;
use App\Models\Video;
use App\Models\VideoUploadSession;
use App\Services\Videos\Contracts\VideoServiceInterface;
use App\Services\Videos\Contracts\VideoUploadServiceInterface;
use App\Services\Videos\VideoUploadSessionQueryService;
use Illuminate\Http\JsonResponse;

class VideoUploadSessionController extends Controller
{
    use AdminAuthenticates;

    public function __construct(
        private readonly VideoServiceInterface $videoService,
        private readonly VideoUploadSessionQueryService $queryService
    ) {}

    /**
     * Create a video upload session.
     */
    public function store(StoreVideoUploadSessionRequest $request, Center $center): JsonResponse
    {
        $admin = $this->requireAdmin();
        $video = Video::findOrFail((int) $request->integer('video_id'));

        if ((int) $video->center_id !== (int) $center->id) {
            $this->notFound('Video not found.');
        }

        /** @var VideoUploadServiceInterface $uploadService */
        $uploadService = app(VideoUploadServiceInterface::class);

        $session = $uploadService->initializeUpload(
            admin: $admin,
            center: $center,
            originalFilename: (string) $request->input('original_filename'),
            video: $video
        );

        return response()->json([
            'success' => true,
            'data' => $this->formatUploadSessionPayload($session),
        ], 201);
    }

    /**
     * Create video metadata and initialize upload session in a single call.
     */
    public function createUpload(CreateVideoUploadRequest $request, Center $center): JsonResponse
    {
        $admin = $this->requireAdmin();
        /** @var array<string, mixed> $validated */
        $validated = $request->validated();

        $videoPayload = $validated;
        unset($videoPayload['original_filename'], $videoPayload['file_size_bytes'], $videoPayload['mime_type']);

        $video = $this->videoService->create($center, $admin, $videoPayload);

        /** @var VideoUploadServiceInterface $uploadService */
        $uploadService = app(VideoUploadServiceInterface::class);

        $session = $uploadService->initializeUpload(
            admin: $admin,
            center: $center,
            originalFilename: (string) $validated['original_filename'],
            video: $video
        );

        return response()->json([
            'success' => true,
            'data' => [
                'video' => new VideoResource($video->loadMissing(['center', 'creator'])),
                'upload_session' => $this->formatUploadSessionPayload($session),
            ],
        ], 201);
    }

    /**
     * List upload sessions for the center.
     */
    public function index(ListVideoUploadSessionsRequest $request, Center $center): JsonResponse
    {
        $admin = $this->requireAdmin();
        $filters = $request->filters();
        $paginator = $this->queryService->paginateForCenter($admin, $center, $filters);

        return response()->json([
            'success' => true,
            'data' => VideoUploadSessionStatusResource::collection($paginator->items()),
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    /**
     * Show upload session status.
     */
    public function show(Center $center, VideoUploadSession $videoUploadSession): JsonResponse
    {
        $this->requireAdmin();

        if ((int) $videoUploadSession->center_id !== (int) $center->id) {
            $this->notFound('Video upload session not found.');
        }

        $videoUploadSession->loadMissing(['videos']);

        return response()->json([
            'success' => true,
            'data' => new VideoUploadSessionStatusResource($videoUploadSession),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatUploadSessionPayload(VideoUploadSession $session): array
    {
        /** @var string|null $tusUploadUrl */
        $tusUploadUrl = $session->getAttribute('tus_upload_url');
        /** @var array<string, string|int>|null $presignedHeaders */
        $presignedHeaders = $session->getAttribute('presigned_headers');
        /** @var \DateTimeInterface|null $expiresAt */
        $expiresAt = $session->expires_at;
        $expiresAtString = $expiresAt?->format(DATE_ATOM);

        return [
            'id' => $session->id,
            'provider' => 'bunny',
            'remote_id' => $session->bunny_upload_id,
            'upload_endpoint' => $tusUploadUrl,
            'presigned_headers' => $presignedHeaders,
            'expires_at' => $expiresAtString,
        ];
    }
}
