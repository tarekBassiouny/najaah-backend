<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\VideoAccess\RevokeVideoAccessRequest;
use App\Http\Resources\Admin\VideoAccess\VideoAccessResource;
use App\Models\Center;
use App\Models\User;
use App\Models\VideoAccess;
use App\Services\VideoAccess\Contracts\VideoApprovalServiceInterface;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class VideoAccessController extends Controller
{
    public function __construct(
        private readonly VideoApprovalServiceInterface $service
    ) {}

    public function centerRevoke(RevokeVideoAccessRequest $request, Center $center, VideoAccess $access): JsonResponse
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

        if ((int) $access->center_id !== (int) $center->id) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Video access not found.',
                ],
            ], 404));
        }

        $revoked = $this->service->revoke($admin, $access);

        return response()->json([
            'success' => true,
            'message' => 'Video access revoked successfully',
            'data' => new VideoAccessResource($revoked->loadMissing(['user', 'video', 'course', 'revoker'])),
        ]);
    }
}
