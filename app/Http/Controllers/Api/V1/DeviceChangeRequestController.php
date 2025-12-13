<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Devices\CreateDeviceChangeRequestRequest;
use App\Http\Resources\DeviceChangeRequestResource;
use App\Models\User;
use App\Services\Devices\DeviceChangeService;
use Illuminate\Http\JsonResponse;

class DeviceChangeRequestController extends Controller
{
    public function __construct(
        private readonly DeviceChangeService $service
    ) {}

    public function index(): JsonResponse
    {
        /** @var User|null $student */
        $student = auth('api')->user();

        if (! $student instanceof User) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Authentication required.',
                ],
            ], 401);
        }

        $requests = $student->deviceChangeRequests()->latest()->get();

        return response()->json([
            'success' => true,
            'data' => DeviceChangeRequestResource::collection($requests),
        ]);
    }

    public function store(CreateDeviceChangeRequestRequest $request): JsonResponse
    {
        /** @var User|null $student */
        $student = $request->user();

        if (! $student instanceof User) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Authentication required.',
                ],
            ], 401);
        }

        $deviceRequest = $this->service->create(
            $student,
            $request->input('new_device_id'),
            $request->input('model'),
            $request->input('os_version'),
            $request->input('reason')
        );

        return response()->json([
            'success' => true,
            'message' => 'Device change request created',
            'data' => new DeviceChangeRequestResource($deviceRequest),
        ], 201);
    }
}
