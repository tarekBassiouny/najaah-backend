<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkWhatsApp\ListBulkWhatsAppJobsRequest;
use App\Http\Resources\Admin\VideoAccess\BulkWhatsAppJobListResource;
use App\Http\Resources\Admin\VideoAccess\BulkWhatsAppJobResource;
use App\Models\BulkWhatsAppJob;
use App\Models\Center;
use App\Models\User;
use App\Services\Admin\BulkWhatsAppJobQueryService;
use App\Services\VideoAccess\Contracts\BulkWhatsAppServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BulkWhatsAppJobController extends Controller
{
    public function __construct(
        private readonly BulkWhatsAppServiceInterface $service,
        private readonly BulkWhatsAppJobQueryService $queryService
    ) {}

    public function centerIndex(ListBulkWhatsAppJobsRequest $request, Center $center): JsonResponse
    {
        $admin = $this->requireAdmin($request);

        $paginator = $this->queryService->paginateForCenter($admin, (int) $center->id, $request->filters());

        return $this->listResponse($paginator);
    }

    public function centerShow(Request $request, Center $center, BulkWhatsAppJob $job): JsonResponse
    {
        $this->requireAdmin($request);
        $this->assertJobBelongsToCenter($center, $job);

        return response()->json([
            'success' => true,
            'data' => new BulkWhatsAppJobResource($job->loadMissing(['creator'])),
        ]);
    }

    public function centerPause(Request $request, Center $center, BulkWhatsAppJob $job): JsonResponse
    {
        $admin = $this->requireAdmin($request);
        $this->assertJobBelongsToCenter($center, $job);

        $paused = $this->service->pause($admin, $job);

        return response()->json([
            'success' => true,
            'message' => 'Bulk job paused successfully.',
            'data' => new BulkWhatsAppJobResource($paused->loadMissing(['creator'])),
        ]);
    }

    public function centerResume(Request $request, Center $center, BulkWhatsAppJob $job): JsonResponse
    {
        $admin = $this->requireAdmin($request);
        $this->assertJobBelongsToCenter($center, $job);

        $resumed = $this->service->resume($admin, $job);

        return response()->json([
            'success' => true,
            'message' => 'Bulk job resumed successfully.',
            'data' => new BulkWhatsAppJobResource($resumed->loadMissing(['creator'])),
        ]);
    }

    public function centerRetryFailed(Request $request, Center $center, BulkWhatsAppJob $job): JsonResponse
    {
        $admin = $this->requireAdmin($request);
        $this->assertJobBelongsToCenter($center, $job);

        $retried = $this->service->retryFailed($admin, $job);

        return response()->json([
            'success' => true,
            'message' => 'Failed items re-queued successfully.',
            'data' => new BulkWhatsAppJobResource($retried->loadMissing(['creator'])),
        ]);
    }

    public function centerCancel(Request $request, Center $center, BulkWhatsAppJob $job): JsonResponse
    {
        $admin = $this->requireAdmin($request);
        $this->assertJobBelongsToCenter($center, $job);

        $cancelled = $this->service->cancel($admin, $job);

        return response()->json([
            'success' => true,
            'message' => 'Bulk job cancelled successfully.',
            'data' => new BulkWhatsAppJobResource($cancelled->loadMissing(['creator'])),
        ]);
    }

    private function requireAdmin(Request $request): User
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

    private function assertJobBelongsToCenter(Center $center, BulkWhatsAppJob $job): void
    {
        if ((int) $job->center_id !== (int) $center->id) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Bulk job not found.',
                ],
            ], 404));
        }
    }

    /**
     * @param  LengthAwarePaginator<BulkWhatsAppJob>  $paginator
     */
    private function listResponse(LengthAwarePaginator $paginator): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Bulk WhatsApp jobs retrieved successfully',
            'data' => BulkWhatsAppJobListResource::collection($paginator->items()),
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }
}
