<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Videos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Videos\ListVideosRequest;
use App\Http\Requests\Admin\Videos\StoreVideoRequest;
use App\Http\Requests\Admin\Videos\UpdateVideoRequest;
use App\Http\Resources\Admin\Videos\AdminVideoResource;
use App\Http\Resources\Admin\Videos\VideoResource;
use App\Models\Center;
use App\Models\User;
use App\Models\Video;
use App\Services\Videos\AdminVideoQueryService;
use App\Services\Videos\VideoService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class VideoController extends Controller
{
    public function __construct(
        private readonly VideoService $videoService,
        private readonly AdminVideoQueryService $queryService
    ) {}

    public function index(ListVideosRequest $request, Center $center): JsonResponse
    {
        $admin = $this->requireAdmin();

        $perPage = (int) $request->integer('per_page', 15);
        /** @var array<string, mixed> $filters */
        $filters = $request->validated();

        $paginator = $this->queryService->paginateForCenter($admin, $center, $perPage, $filters);

        return response()->json([
            'success' => true,
            'data' => AdminVideoResource::collection($paginator->items()),
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function store(StoreVideoRequest $request, Center $center): JsonResponse
    {
        $admin = $this->requireAdmin();
        /** @var array<string, mixed> $data */
        $data = $request->validated();

        $video = $this->videoService->create($center, $admin, $data);

        return response()->json([
            'success' => true,
            'data' => new VideoResource($video),
        ], 201);
    }

    public function show(Center $center, Video $video): JsonResponse
    {
        $this->requireAdmin();
        $this->assertVideoBelongsToCenter($center, $video);

        return response()->json([
            'success' => true,
            'data' => new VideoResource($video->load(['uploadSession', 'creator'])),
        ]);
    }

    public function update(UpdateVideoRequest $request, Center $center, Video $video): JsonResponse
    {
        $admin = $this->requireAdmin();
        $this->assertVideoBelongsToCenter($center, $video);
        /** @var array<string, mixed> $data */
        $data = $request->validated();

        $updated = $this->videoService->update($video, $admin, $data);

        return response()->json([
            'success' => true,
            'data' => new VideoResource($updated),
        ]);
    }

    public function destroy(Center $center, Video $video): JsonResponse
    {
        $admin = $this->requireAdmin();
        $this->assertVideoBelongsToCenter($center, $video);
        $this->videoService->delete($video, $admin);

        return response()->json([
            'success' => true,
            'data' => null,
        ], 204);
    }

    private function assertVideoBelongsToCenter(Center $center, Video $video): void
    {
        if ((int) $video->center_id !== (int) $center->id) {
            $this->notFound();
        }
    }

    private function requireAdmin(): User
    {
        $admin = request()->user();

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

    private function notFound(): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'error' => [
                'code' => 'NOT_FOUND',
                'message' => 'Video not found.',
            ],
        ], 404));
    }
}
