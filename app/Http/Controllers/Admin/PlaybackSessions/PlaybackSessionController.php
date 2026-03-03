<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\PlaybackSessions;

use App\Http\Controllers\Concerns\AdminAuthenticates;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PlaybackSessions\ListPlaybackSessionsRequest;
use App\Http\Resources\Admin\PlaybackSessions\PlaybackSessionResource;
use App\Models\Center;
use App\Services\Admin\PlaybackSessionQueryService;
use Illuminate\Http\JsonResponse;

class PlaybackSessionController extends Controller
{
    use AdminAuthenticates;

    public function __construct(
        private readonly PlaybackSessionQueryService $queryService
    ) {}

    public function index(ListPlaybackSessionsRequest $request, Center $center): JsonResponse
    {
        $admin = $this->requireAdmin();

        $paginator = $this->queryService->paginateForCenter(
            $admin,
            (int) $center->id,
            $request->filters()
        );

        return response()->json([
            'success' => true,
            'data' => PlaybackSessionResource::collection($paginator->items()),
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }
}
