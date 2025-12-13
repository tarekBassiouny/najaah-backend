<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CenterSettings\UpdateCenterSettingsRequest;
use App\Http\Resources\CenterSettingResource;
use App\Models\Center;
use App\Services\Settings\Contracts\CenterSettingsServiceInterface;
use Illuminate\Http\JsonResponse;

class CenterSettingsController extends Controller
{
    public function __construct(
        private readonly CenterSettingsServiceInterface $centerSettingsService
    ) {}

    public function show(Center $center): JsonResponse
    {
        $setting = $this->centerSettingsService->get($center);

        return response()->json([
            'success' => true,
            'message' => 'Center settings retrieved successfully',
            'data' => new CenterSettingResource($setting),
        ]);
    }

    public function update(UpdateCenterSettingsRequest $request, Center $center): JsonResponse
    {
        /** @var array<string, mixed> $settings */
        $settings = $request->validated('settings');
        $setting = $this->centerSettingsService->update($center, $settings);

        return response()->json([
            'success' => true,
            'message' => 'Center settings updated successfully',
            'data' => new CenterSettingResource($setting),
        ]);
    }
}
