<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExtraViews\ApproveExtraViewRequestRequest;
use App\Http\Requests\ExtraViews\RejectExtraViewRequestRequest;
use App\Http\Resources\ExtraViewRequestResource;
use App\Models\ExtraViewRequest;
use App\Models\User;
use App\Services\Playback\ExtraViewRequestService;
use Illuminate\Http\JsonResponse;

class ExtraViewRequestController extends Controller
{
    public function __construct(
        private readonly ExtraViewRequestService $service
    ) {}

    public function approve(ApproveExtraViewRequestRequest $request, ExtraViewRequest $extraViewRequest): JsonResponse
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

        $approved = $this->service->approve(
            $admin,
            $extraViewRequest,
            (int) $request->integer('granted_views'),
            $request->input('decision_reason')
        );

        return response()->json([
            'success' => true,
            'message' => 'Request approved successfully',
            'data' => new ExtraViewRequestResource($approved),
        ]);
    }

    public function reject(RejectExtraViewRequestRequest $request, ExtraViewRequest $extraViewRequest): JsonResponse
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

        $rejected = $this->service->reject(
            $admin,
            $extraViewRequest,
            $request->input('decision_reason')
        );

        return response()->json([
            'success' => true,
            'message' => 'Request rejected successfully',
            'data' => new ExtraViewRequestResource($rejected),
        ]);
    }
}
