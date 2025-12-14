<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ListVideoUploadSessionsRequest;
use App\Http\Resources\VideoUploadSessionResource;
use App\Models\User;
use App\Services\Videos\VideoUploadSessionQueryService;
use Illuminate\Http\JsonResponse;

class VideoUploadSessionController extends Controller
{
    public function __construct(
        private readonly VideoUploadSessionQueryService $queryService
    ) {}

    public function index(ListVideoUploadSessionsRequest $request): JsonResponse
    {
        /** @var User|null $admin */
        $admin = $request->user();

        if (! $admin instanceof User) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Authentication required.',
                ],
            ], 401);
        }

        $perPage = (int) $request->integer('per_page', 15);
        /** @var array<string, mixed> $filters */
        $filters = $request->only(['status', 'center_id']);

        $paginator = $this->queryService->paginate($admin, $perPage, $filters);

        return response()->json([
            'success' => true,
            'message' => 'Video upload sessions retrieved successfully',
            'data' => VideoUploadSessionResource::collection($paginator->items()),
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }
}
