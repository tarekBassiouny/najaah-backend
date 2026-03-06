<?php

declare(strict_types=1);

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mobile\RedeemVideoAccessCodeRequest;
use App\Models\User;
use App\Services\VideoAccess\Contracts\VideoApprovalCodeServiceInterface;
use Illuminate\Http\JsonResponse;

class VideoAccessCodeController extends Controller
{
    public function __construct(
        private readonly VideoApprovalCodeServiceInterface $service
    ) {}

    public function redeem(RedeemVideoAccessCodeRequest $request): JsonResponse
    {
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

        $access = $this->service->redeem($student, (string) $request->input('code'));

        return response()->json([
            'success' => true,
            'message' => 'Video unlocked successfully.',
            'data' => [
                'id' => $access->id,
                'video_id' => $access->video_id,
                'course_id' => $access->course_id,
                'granted_at' => $access->granted_at,
            ],
        ]);
    }
}
