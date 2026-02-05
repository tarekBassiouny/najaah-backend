<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Videos;

use App\Http\Controllers\Concerns\AdminAuthenticates;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Videos\ListVideosRequest;
use App\Http\Requests\Admin\Videos\StoreVideoRequest;
use App\Http\Requests\Admin\Videos\UpdateVideoRequest;
use App\Http\Resources\Admin\Videos\AdminVideoResource;
use App\Http\Resources\Admin\Videos\VideoResource;
use App\Models\Center;
use App\Models\Video;
use App\Services\Videos\Contracts\AdminVideoQueryServiceInterface;
use App\Services\Videos\Contracts\VideoServiceInterface;
use Illuminate\Http\JsonResponse;

class VideoController extends Controller
{
    use AdminAuthenticates;

    public function __construct(
        private readonly VideoServiceInterface $videoService,
        private readonly AdminVideoQueryServiceInterface $queryService
    ) {}

    public function index(ListVideosRequest $request, Center $center): JsonResponse
    {
        $admin = $this->requireAdmin();

        $filters = $request->filters();

        $paginator = $this->queryService->paginateForCenter($admin, $center, $filters);

        return response()->json([
            'success' => true,
            'data' => AdminVideoResource::collection($paginator->items()),
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
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
            'data' => new VideoResource($video->loadMissing(['center', 'creator'])),
        ], 201);
    }

    public function show(Center $center, Video $video): JsonResponse
    {
        $this->requireAdmin();
        $this->assertVideoBelongsToCenter($center, $video);

        return response()->json([
            'success' => true,
            'data' => new VideoResource($video->load(['center', 'uploadSession', 'creator'])),
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
            'data' => new VideoResource($updated->loadMissing(['center', 'creator'])),
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
            $this->notFound('Video not found.');
        }
    }
}
