<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Centers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Centers\RetryCenterOnboardingRequest;
use App\Http\Resources\Admin\Centers\CenterResource;
use App\Http\Resources\Admin\Users\AdminUserResource;
use App\Models\Center;
use App\Services\Centers\CenterOnboardingService;
use Illuminate\Http\JsonResponse;

class CenterOnboardingController extends Controller
{
    public function __construct(private readonly CenterOnboardingService $onboardingService) {}

    public function retry(
        RetryCenterOnboardingRequest $request,
        int $center,
    ): JsonResponse {
        $center = Center::find($center);

        if ($center === null) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Center not found',
                ],
            ], 404);
        }

        $result = $this->onboardingService->resume($center, null, null, 'center_owner');

        return response()->json([
            'success' => true,
            'data' => [
                'center' => new CenterResource($result['center']),
                'owner' => new AdminUserResource($result['owner']),
                'email_sent' => $result['email_sent'],
            ],
        ]);
    }
}
