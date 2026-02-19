<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Filters\Admin\DeviceChangeRequestFilters;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Devices\ApproveDeviceChangeRequest;
use App\Http\Requests\Admin\Devices\BulkApproveDeviceChangeRequestsRequest;
use App\Http\Requests\Admin\Devices\BulkPreApproveDeviceChangeRequestsRequest;
use App\Http\Requests\Admin\Devices\BulkRejectDeviceChangeRequestsRequest;
use App\Http\Requests\Admin\Devices\CreateDeviceChangeForStudentRequest;
use App\Http\Requests\Admin\Devices\ListDeviceChangeRequestsRequest;
use App\Http\Requests\Admin\Devices\PreApproveDeviceChangeRequest;
use App\Http\Requests\Admin\Devices\RejectDeviceChangeRequest;
use App\Http\Resources\Admin\Devices\DeviceChangeRequestListResource;
use App\Http\Resources\Admin\Devices\DeviceChangeRequestResource;
use App\Models\Center;
use App\Models\DeviceChangeRequest;
use App\Models\User;
use App\Services\Admin\DeviceChangeRequestQueryService;
use App\Services\Devices\DeviceChangeService;
use App\Support\ErrorCodes;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class DeviceChangeRequestController extends Controller
{
    public function __construct(
        private readonly DeviceChangeService $service,
        private readonly DeviceChangeRequestQueryService $queryService
    ) {}

    /**
     * List device change requests in system scope.
     */
    public function systemIndex(ListDeviceChangeRequestsRequest $request): JsonResponse
    {
        $admin = $this->requireAdmin($request);
        $paginator = $this->queryService->paginate($admin, $request->filters());

        return $this->listResponse($paginator);
    }

    /**
     * List device change requests in center scope.
     */
    public function centerIndex(ListDeviceChangeRequestsRequest $request, Center $center): JsonResponse
    {
        $admin = $this->requireAdmin($request);
        $paginator = $this->queryService->paginateForCenter(
            $admin,
            (int) $center->id,
            $this->forCenter($request->filters(), (int) $center->id)
        );

        return $this->listResponse($paginator);
    }

    /**
     * Approve a device change request in system scope.
     */
    public function systemApprove(
        ApproveDeviceChangeRequest $request,
        DeviceChangeRequest $deviceChangeRequest
    ): JsonResponse {
        $admin = $this->requireAdmin($request);

        /** @var array<string, mixed> $data */
        $data = $request->validated();

        $approved = $this->service->approve(
            $admin,
            $deviceChangeRequest,
            $data['new_device_id'] ?? null,
            $data['new_model'] ?? null,
            $data['new_os_version'] ?? null
        );
        $message = $approved->status === DeviceChangeRequest::STATUS_PRE_APPROVED
            ? 'Device change request pre-approved'
            : 'Device change request approved';

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => new DeviceChangeRequestResource($approved->loadMissing(['user', 'center', 'decider'])),
        ]);
    }

    /**
     * Approve a device change request in center scope.
     */
    public function centerApprove(
        ApproveDeviceChangeRequest $request,
        Center $center,
        DeviceChangeRequest $deviceChangeRequest
    ): JsonResponse {
        $admin = $this->requireAdmin($request);
        $this->assertRequestBelongsToCenter($center, $deviceChangeRequest);

        /** @var array<string, mixed> $data */
        $data = $request->validated();

        $approved = $this->service->approve(
            $admin,
            $deviceChangeRequest,
            $data['new_device_id'] ?? null,
            $data['new_model'] ?? null,
            $data['new_os_version'] ?? null
        );
        $message = $approved->status === DeviceChangeRequest::STATUS_PRE_APPROVED
            ? 'Device change request pre-approved'
            : 'Device change request approved';

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => new DeviceChangeRequestResource($approved->loadMissing(['user', 'center', 'decider'])),
        ]);
    }

    /**
     * Reject a device change request in system scope.
     */
    public function systemReject(
        RejectDeviceChangeRequest $request,
        DeviceChangeRequest $deviceChangeRequest
    ): JsonResponse {
        $admin = $this->requireAdmin($request);
        $rejected = $this->service->reject($admin, $deviceChangeRequest, $request->input('decision_reason'));

        return response()->json([
            'success' => true,
            'message' => 'Device change request rejected',
            'data' => new DeviceChangeRequestResource($rejected->loadMissing(['user', 'center', 'decider'])),
        ]);
    }

    /**
     * Reject a device change request in center scope.
     */
    public function centerReject(
        RejectDeviceChangeRequest $request,
        Center $center,
        DeviceChangeRequest $deviceChangeRequest
    ): JsonResponse {
        $admin = $this->requireAdmin($request);
        $this->assertRequestBelongsToCenter($center, $deviceChangeRequest);

        $rejected = $this->service->reject($admin, $deviceChangeRequest, $request->input('decision_reason'));

        return response()->json([
            'success' => true,
            'message' => 'Device change request rejected',
            'data' => new DeviceChangeRequestResource($rejected->loadMissing(['user', 'center', 'decider'])),
        ]);
    }

    /**
     * Pre-approve a device change request in system scope.
     */
    public function systemPreApprove(
        PreApproveDeviceChangeRequest $request,
        DeviceChangeRequest $deviceChangeRequest
    ): JsonResponse {
        $admin = $this->requireAdmin($request);
        $preApproved = $this->service->preApprove($admin, $deviceChangeRequest, $request->input('decision_reason'));

        return response()->json([
            'success' => true,
            'message' => 'Device change request pre-approved',
            'data' => new DeviceChangeRequestResource($preApproved->loadMissing(['user', 'center', 'decider'])),
        ]);
    }

    /**
     * Pre-approve a device change request in center scope.
     */
    public function centerPreApprove(
        PreApproveDeviceChangeRequest $request,
        Center $center,
        DeviceChangeRequest $deviceChangeRequest
    ): JsonResponse {
        $admin = $this->requireAdmin($request);
        $this->assertRequestBelongsToCenter($center, $deviceChangeRequest);

        $preApproved = $this->service->preApprove($admin, $deviceChangeRequest, $request->input('decision_reason'));

        return response()->json([
            'success' => true,
            'message' => 'Device change request pre-approved',
            'data' => new DeviceChangeRequestResource($preApproved->loadMissing(['user', 'center', 'decider'])),
        ]);
    }

    /**
     * Create a device change request for a student in system scope.
     */
    public function systemCreateForStudent(
        CreateDeviceChangeForStudentRequest $request,
        User $student
    ): JsonResponse {
        $admin = $this->requireAdmin($request);

        if (! $student->is_student) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => ErrorCodes::NOT_STUDENT,
                    'message' => 'The specified user is not a student.',
                ],
            ], 422);
        }

        $created = $this->service->createByAdmin($admin, $student, $request->input('reason'));

        return response()->json([
            'success' => true,
            'message' => 'Device change request created for student',
            'data' => new DeviceChangeRequestResource($created->loadMissing(['user', 'center', 'decider'])),
        ], 201);
    }

    /**
     * Create a device change request for a student in center scope.
     */
    public function centerCreateForStudent(
        CreateDeviceChangeForStudentRequest $request,
        Center $center,
        User $student
    ): JsonResponse {
        $admin = $this->requireAdmin($request);

        if (! $student->is_student) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => ErrorCodes::NOT_STUDENT,
                    'message' => 'The specified user is not a student.',
                ],
            ], 422);
        }

        if ((int) $student->center_id !== (int) $center->id) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Student not found.',
                ],
            ], 404);
        }

        $created = $this->service->createByAdmin($admin, $student, $request->input('reason'));

        return response()->json([
            'success' => true,
            'message' => 'Device change request created for student',
            'data' => new DeviceChangeRequestResource($created->loadMissing(['user', 'center', 'decider'])),
        ], 201);
    }

    /**
     * Bulk approve device change requests in system scope.
     */
    public function systemBulkApprove(BulkApproveDeviceChangeRequestsRequest $request): JsonResponse
    {
        $admin = $this->requireAdmin($request);
        /** @var array{request_ids:array<int,int>,new_device_id:?string,new_model:?string,new_os_version:?string} $data */
        $data = $request->validated();

        $result = $this->bulkApprove(
            $admin,
            $data['request_ids'],
            $data['new_device_id'] ?? null,
            $data['new_model'] ?? null,
            $data['new_os_version'] ?? null,
            null
        );

        return $this->bulkResponse('Bulk device approval processed', $data['request_ids'], $result, 'approved');
    }

    /**
     * Bulk approve device change requests in center scope.
     */
    public function centerBulkApprove(BulkApproveDeviceChangeRequestsRequest $request, Center $center): JsonResponse
    {
        $admin = $this->requireAdmin($request);
        /** @var array{request_ids:array<int,int>,new_device_id:?string,new_model:?string,new_os_version:?string} $data */
        $data = $request->validated();

        $result = $this->bulkApprove(
            $admin,
            $data['request_ids'],
            $data['new_device_id'] ?? null,
            $data['new_model'] ?? null,
            $data['new_os_version'] ?? null,
            (int) $center->id
        );

        return $this->bulkResponse('Bulk device approval processed', $data['request_ids'], $result, 'approved');
    }

    /**
     * Bulk reject device change requests in system scope.
     */
    public function systemBulkReject(BulkRejectDeviceChangeRequestsRequest $request): JsonResponse
    {
        $admin = $this->requireAdmin($request);
        /** @var array{request_ids:array<int,int>,decision_reason:?string} $data */
        $data = $request->validated();

        $result = $this->bulkReject($admin, $data['request_ids'], $data['decision_reason'] ?? null, null);

        return $this->bulkResponse('Bulk device rejection processed', $data['request_ids'], $result, 'rejected');
    }

    /**
     * Bulk reject device change requests in center scope.
     */
    public function centerBulkReject(BulkRejectDeviceChangeRequestsRequest $request, Center $center): JsonResponse
    {
        $admin = $this->requireAdmin($request);
        /** @var array{request_ids:array<int,int>,decision_reason:?string} $data */
        $data = $request->validated();

        $result = $this->bulkReject($admin, $data['request_ids'], $data['decision_reason'] ?? null, (int) $center->id);

        return $this->bulkResponse('Bulk device rejection processed', $data['request_ids'], $result, 'rejected');
    }

    /**
     * Bulk pre-approve device change requests in system scope.
     */
    public function systemBulkPreApprove(BulkPreApproveDeviceChangeRequestsRequest $request): JsonResponse
    {
        $admin = $this->requireAdmin($request);
        /** @var array{request_ids:array<int,int>,decision_reason:?string} $data */
        $data = $request->validated();

        $result = $this->bulkPreApprove($admin, $data['request_ids'], $data['decision_reason'] ?? null, null);

        return $this->bulkResponse('Bulk device pre-approval processed', $data['request_ids'], $result, 'pre_approved');
    }

    /**
     * Bulk pre-approve device change requests in center scope.
     */
    public function centerBulkPreApprove(BulkPreApproveDeviceChangeRequestsRequest $request, Center $center): JsonResponse
    {
        $admin = $this->requireAdmin($request);
        /** @var array{request_ids:array<int,int>,decision_reason:?string} $data */
        $data = $request->validated();

        $result = $this->bulkPreApprove($admin, $data['request_ids'], $data['decision_reason'] ?? null, (int) $center->id);

        return $this->bulkResponse('Bulk device pre-approval processed', $data['request_ids'], $result, 'pre_approved');
    }

    private function assertRequestBelongsToCenter(Center $center, DeviceChangeRequest $request): void
    {
        if ((int) $request->center_id !== (int) $center->id) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Device change request not found.',
                ],
            ], 404));
        }
    }

    /**
     * @param  array<int, int|string>  $requestIds
     * @return array{
     *   approved: array<int, DeviceChangeRequest>,
     *   skipped: array<int, int|string>,
     *   failed: array<int, array{request_id: int|string, reason: string}>
     * }
     */
    private function bulkApprove(
        User $admin,
        array $requestIds,
        ?string $newDeviceId,
        ?string $newModel,
        ?string $newOsVersion,
        ?int $centerId
    ): array {
        $uniqueIds = array_values(array_unique(array_map('intval', $requestIds)));
        $query = DeviceChangeRequest::query()->whereIn('id', $uniqueIds);

        if ($centerId !== null) {
            $query->where('center_id', $centerId);
        }

        $requests = $query->get()->keyBy('id');

        $result = [
            'approved' => [],
            'skipped' => [],
            'failed' => [],
        ];

        foreach ($uniqueIds as $requestId) {
            $deviceRequest = $requests->get($requestId);

            if (! $deviceRequest instanceof DeviceChangeRequest) {
                $result['failed'][] = [
                    'request_id' => $requestId,
                    'reason' => 'Device change request not found.',
                ];

                continue;
            }

            if ($deviceRequest->status !== DeviceChangeRequest::STATUS_PENDING) {
                $result['skipped'][] = $requestId;

                continue;
            }

            try {
                $result['approved'][] = $this->service->approve(
                    $admin,
                    $deviceRequest,
                    $newDeviceId,
                    $newModel,
                    $newOsVersion
                );
            } catch (\Throwable $exception) {
                $result['failed'][] = [
                    'request_id' => $requestId,
                    'reason' => $exception->getMessage(),
                ];
            }
        }

        return $result;
    }

    /**
     * @param  array<int, int|string>  $requestIds
     * @return array{
     *   rejected: array<int, DeviceChangeRequest>,
     *   skipped: array<int, int|string>,
     *   failed: array<int, array{request_id: int|string, reason: string}>
     * }
     */
    private function bulkReject(
        User $admin,
        array $requestIds,
        ?string $decisionReason,
        ?int $centerId
    ): array {
        $uniqueIds = array_values(array_unique(array_map('intval', $requestIds)));
        $query = DeviceChangeRequest::query()->whereIn('id', $uniqueIds);

        if ($centerId !== null) {
            $query->where('center_id', $centerId);
        }

        $requests = $query->get()->keyBy('id');

        $result = [
            'rejected' => [],
            'skipped' => [],
            'failed' => [],
        ];

        foreach ($uniqueIds as $requestId) {
            $deviceRequest = $requests->get($requestId);

            if (! $deviceRequest instanceof DeviceChangeRequest) {
                $result['failed'][] = [
                    'request_id' => $requestId,
                    'reason' => 'Device change request not found.',
                ];

                continue;
            }

            if ($deviceRequest->status !== DeviceChangeRequest::STATUS_PENDING) {
                $result['skipped'][] = $requestId;

                continue;
            }

            try {
                $result['rejected'][] = $this->service->reject($admin, $deviceRequest, $decisionReason);
            } catch (\Throwable $exception) {
                $result['failed'][] = [
                    'request_id' => $requestId,
                    'reason' => $exception->getMessage(),
                ];
            }
        }

        return $result;
    }

    /**
     * @param  array<int, int|string>  $requestIds
     * @return array{
     *   pre_approved: array<int, DeviceChangeRequest>,
     *   skipped: array<int, int|string>,
     *   failed: array<int, array{request_id: int|string, reason: string}>
     * }
     */
    private function bulkPreApprove(
        User $admin,
        array $requestIds,
        ?string $decisionReason,
        ?int $centerId
    ): array {
        $uniqueIds = array_values(array_unique(array_map('intval', $requestIds)));
        $query = DeviceChangeRequest::query()->whereIn('id', $uniqueIds);

        if ($centerId !== null) {
            $query->where('center_id', $centerId);
        }

        $requests = $query->get()->keyBy('id');

        $result = [
            'pre_approved' => [],
            'skipped' => [],
            'failed' => [],
        ];

        foreach ($uniqueIds as $requestId) {
            $deviceRequest = $requests->get($requestId);

            if (! $deviceRequest instanceof DeviceChangeRequest) {
                $result['failed'][] = [
                    'request_id' => $requestId,
                    'reason' => 'Device change request not found.',
                ];

                continue;
            }

            if ($deviceRequest->status !== DeviceChangeRequest::STATUS_PENDING) {
                $result['skipped'][] = $requestId;

                continue;
            }

            try {
                $result['pre_approved'][] = $this->service->preApprove($admin, $deviceRequest, $decisionReason);
            } catch (\Throwable $exception) {
                $result['failed'][] = [
                    'request_id' => $requestId,
                    'reason' => $exception->getMessage(),
                ];
            }
        }

        return $result;
    }

    private function requireAdmin(
        ListDeviceChangeRequestsRequest|ApproveDeviceChangeRequest|RejectDeviceChangeRequest|PreApproveDeviceChangeRequest|CreateDeviceChangeForStudentRequest|BulkApproveDeviceChangeRequestsRequest|BulkRejectDeviceChangeRequestsRequest|BulkPreApproveDeviceChangeRequestsRequest $request
    ): User {
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

    private function forCenter(DeviceChangeRequestFilters $filters, int $centerId): DeviceChangeRequestFilters
    {
        return new DeviceChangeRequestFilters(
            page: $filters->page,
            perPage: $filters->perPage,
            status: $filters->status,
            centerId: $centerId,
            userId: $filters->userId,
            search: $filters->search,
            requestSource: $filters->requestSource,
            decidedBy: $filters->decidedBy,
            currentDeviceId: $filters->currentDeviceId,
            newDeviceId: $filters->newDeviceId,
            dateFrom: $filters->dateFrom,
            dateTo: $filters->dateTo
        );
    }

    /**
     * @param  \Illuminate\Contracts\Pagination\LengthAwarePaginator<DeviceChangeRequest>  $paginator
     */
    private function listResponse(LengthAwarePaginator $paginator): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Device change requests retrieved successfully',
            'data' => DeviceChangeRequestListResource::collection($paginator->items()),
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    /**
     * @param  array<int, int|string>  $requestIds
     * @param  array{
     *   skipped: array<int, int|string>,
     *   failed: array<int, array{request_id: int|string, reason: string}>,
     *   approved?: array<int, DeviceChangeRequest>,
     *   rejected?: array<int, DeviceChangeRequest>,
     *   pre_approved?: array<int, DeviceChangeRequest>
     * }  $result
     */
    private function bulkResponse(string $message, array $requestIds, array $result, string $processedKey): JsonResponse
    {
        /** @var array<int, DeviceChangeRequest> $processed */
        $processed = $result[$processedKey] ?? [];

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [
                'counts' => [
                    'total' => count(array_values(array_unique(array_map('intval', $requestIds)))),
                    $processedKey => count($processed),
                    'skipped' => count($result['skipped']),
                    'failed' => count($result['failed']),
                ],
                $processedKey => DeviceChangeRequestResource::collection(
                    collect($processed)->map(
                        fn (DeviceChangeRequest $request) => $request->loadMissing(['user', 'center', 'decider'])
                    )
                ),
                'skipped' => $result['skipped'],
                'failed' => $result['failed'],
            ],
        ]);
    }
}
