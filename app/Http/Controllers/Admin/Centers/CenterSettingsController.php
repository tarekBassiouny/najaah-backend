<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Centers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Centers\UpdateCenterSettingsRequest;
use App\Http\Resources\Admin\Centers\CenterSettingResource;
use App\Models\Center;
use App\Models\User;
use App\Services\Settings\Contracts\CenterSettingsServiceInterface;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class CenterSettingsController extends Controller
{
    public function __construct(
        private readonly CenterSettingsServiceInterface $centerSettingsService
    ) {}

    public function show(Center $center): JsonResponse
    {
        $admin = $this->requireAdmin();
        $setting = $this->centerSettingsService->get($admin, $center);

        return response()->json([
            'success' => true,
            'message' => 'Center settings retrieved successfully',
            'data' => new CenterSettingResource($setting),
        ]);
    }

    public function update(UpdateCenterSettingsRequest $request, Center $center): JsonResponse
    {
        $admin = $this->requireAdmin();
        /** @var array<string, mixed> $settings */
        $settings = $request->validated('settings');
        $setting = $this->centerSettingsService->update($admin, $center, $settings);

        return response()->json([
            'success' => true,
            'message' => 'Center settings updated successfully',
            'data' => new CenterSettingResource($setting),
        ]);
    }

    private function requireAdmin(): User
    {
        $admin = request()->user();

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
}
