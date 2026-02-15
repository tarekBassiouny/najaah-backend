<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ExtraViews\ApproveExtraViewRequestRequest;
use App\Http\Requests\Admin\ExtraViews\ListExtraViewRequestsRequest;
use App\Http\Requests\Admin\ExtraViews\RejectExtraViewRequestRequest;
use App\Http\Resources\Admin\ExtraViews\ExtraViewRequestListResource;
use App\Http\Resources\Admin\ExtraViews\ExtraViewRequestResource;
use App\Models\Center;
use App\Models\ExtraViewRequest;
use App\Models\User;
use App\Services\Admin\ExtraViewRequestQueryService;
use App\Services\Playback\ExtraViewRequestService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class ExtraViewRequestController extends Controller
{
    public function __construct(
        private readonly ExtraViewRequestService $service,
        private readonly ExtraViewRequestQueryService $queryService
    ) {}

    /**
     * List extra view requests.
     */
    public function index(ListExtraViewRequestsRequest $request, Center $center): JsonResponse
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

        $filters = $request->filters();
        $paginator = $this->queryService->paginateForCenter($admin, (int) $center->id, $filters);

        return response()->json([
            'success' => true,
            'message' => 'Extra view requests retrieved successfully',
            'data' => ExtraViewRequestListResource::collection($paginator->items()),
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    /**
     * Approve an extra view request.
     */
    public function approve(
        ApproveExtraViewRequestRequest $request,
        Center $center,
        ExtraViewRequest $extraViewRequest
    ): JsonResponse {
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

        $this->assertRequestBelongsToCenter($center, $extraViewRequest);

        $approved = $this->service->approve(
            $admin,
            $extraViewRequest,
            (int) $request->integer('granted_views'),
            $request->input('decision_reason')
        );

        return response()->json([
            'success' => true,
            'message' => 'Request approved successfully',
            'data' => new ExtraViewRequestResource($approved->loadMissing(['user', 'video', 'course', 'center', 'decider'])),
        ]);
    }

    /**
     * Reject an extra view request.
     */
    public function reject(
        RejectExtraViewRequestRequest $request,
        Center $center,
        ExtraViewRequest $extraViewRequest
    ): JsonResponse {
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

        $this->assertRequestBelongsToCenter($center, $extraViewRequest);

        $rejected = $this->service->reject(
            $admin,
            $extraViewRequest,
            $request->input('decision_reason')
        );

        return response()->json([
            'success' => true,
            'message' => 'Request rejected successfully',
            'data' => new ExtraViewRequestResource($rejected->loadMissing(['user', 'video', 'course', 'center', 'decider'])),
        ]);
    }

    private function assertRequestBelongsToCenter(Center $center, ExtraViewRequest $request): void
    {
        if ((int) $request->center_id !== (int) $center->id) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Extra view request not found.',
                ],
            ], 404));
        }
    }
}
