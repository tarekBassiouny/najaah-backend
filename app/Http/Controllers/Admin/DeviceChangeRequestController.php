<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Devices\ApproveDeviceChangeRequest;
use App\Http\Requests\Admin\Devices\ListDeviceChangeRequestsRequest;
use App\Http\Requests\Admin\Devices\RejectDeviceChangeRequest;
use App\Http\Resources\Admin\Devices\DeviceChangeRequestListResource;
use App\Http\Resources\Admin\Devices\DeviceChangeRequestResource;
use App\Models\DeviceChangeRequest;
use App\Models\User;
use App\Services\Admin\DeviceChangeRequestQueryService;
use App\Services\Devices\DeviceChangeService;
use Illuminate\Http\JsonResponse;

class DeviceChangeRequestController extends Controller
{
    public function __construct(
        private readonly DeviceChangeService $service,
        private readonly DeviceChangeRequestQueryService $queryService
    ) {}

    public function index(ListDeviceChangeRequestsRequest $request): JsonResponse
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
        $filters = $request->validated();
        $paginator = $this->queryService->build($admin, $filters)->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Device change requests retrieved successfully',
            'data' => DeviceChangeRequestListResource::collection($paginator->items()),
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function approve(ApproveDeviceChangeRequest $request, DeviceChangeRequest $deviceChangeRequest): JsonResponse
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

        /** @var array<string, mixed> $data */
        $data = $request->validated();

        $approved = $this->service->approve(
            $admin,
            $deviceChangeRequest,
            $data['new_device_id'] ?? null,
            $data['new_model'] ?? null,
            $data['new_os_version'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => 'Device change request approved',
            'data' => new DeviceChangeRequestResource($approved),
        ]);
    }

    public function reject(RejectDeviceChangeRequest $request, DeviceChangeRequest $deviceChangeRequest): JsonResponse
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

        $rejected = $this->service->reject($admin, $deviceChangeRequest, $request->input('decision_reason'));

        return response()->json([
            'success' => true,
            'message' => 'Device change request rejected',
            'data' => new DeviceChangeRequestResource($rejected),
        ]);
    }
}
