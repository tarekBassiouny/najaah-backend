<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Centers;

use App\Actions\Admin\Centers\RetryCenterOnboardingAction;
use App\Actions\Admin\Centers\UploadCenterLogoAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Centers\BulkRetryCenterOnboardingRequest;
use App\Http\Requests\Admin\Centers\RetryCenterOnboardingRequest;
use App\Http\Requests\Admin\Centers\UpdateCenterSettingsRequest;
use App\Http\Requests\Admin\Centers\UploadCenterLogoRequest;
use App\Http\Resources\Admin\Centers\CenterResource;
use App\Http\Resources\Admin\Centers\CenterSettingResource;
use App\Http\Resources\Admin\Users\AdminUserResource;
use App\Models\Center;
use App\Models\User;
use App\Services\Settings\Contracts\CenterSettingsServiceInterface;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class CenterOperationsController extends Controller
{
    public function __construct(
        private readonly CenterSettingsServiceInterface $centerSettingsService
    ) {}

    /**
     * Retry center setup.
     */
    public function retry(
        RetryCenterOnboardingRequest $request,
        int $center,
        RetryCenterOnboardingAction $action
    ): JsonResponse {
        /** @var User|null $admin */
        $admin = $request->user();
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

        $result = $action->execute($center, $admin instanceof User ? $admin : null);

        return response()->json([
            'success' => true,
            'data' => [
                'center' => new CenterResource($result['center']),
                'owner' => new AdminUserResource($result['owner']),
                'email_sent' => $result['email_sent'],
            ],
        ]);
    }

    /**
     * Bulk retry center setup.
     */
    public function bulkRetry(
        BulkRetryCenterOnboardingRequest $request,
        RetryCenterOnboardingAction $action
    ): JsonResponse {
        /** @var array{center_ids:array<int,int>} $data */
        $data = $request->validated();
        $requestedIds = $this->uniqueCenterIds($data['center_ids']);
        $centers = Center::query()
            ->whereIn('id', $requestedIds)
            ->get()
            ->keyBy('id');

        /** @var User|null $admin */
        $admin = $request->user();
        $retried = [];
        $failed = [];

        foreach ($requestedIds as $centerId) {
            $centerModel = $centers->get($centerId);
            if (! $centerModel instanceof Center) {
                $failed[] = [
                    'center_id' => $centerId,
                    'reason' => 'Center not found.',
                ];

                continue;
            }

            try {
                $result = $action->execute($centerModel, $admin instanceof User ? $admin : null);
                $retried[] = [
                    'center' => new CenterResource($result['center']),
                    'owner' => new AdminUserResource($result['owner']),
                    'email_sent' => (bool) $result['email_sent'],
                ];
            } catch (\Throwable $throwable) {
                $failed[] = [
                    'center_id' => $centerId,
                    'reason' => 'Center onboarding retry failed.',
                ];
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Bulk center onboarding retry processed',
            'data' => [
                'counts' => [
                    'total' => count($requestedIds),
                    'retried' => count($retried),
                    'failed' => count($failed),
                ],
                'retried' => $retried,
                'failed' => $failed,
            ],
        ]);
    }

    /**
     * Upload a center logo.
     */
    public function uploadLogo(
        UploadCenterLogoRequest $request,
        int $center,
        UploadCenterLogoAction $action
    ): JsonResponse {
        /** @var User|null $admin */
        $admin = $request->user();
        $centerModel = Center::find($center);

        if ($centerModel === null) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Center not found',
                ],
            ], 404);
        }

        /** @var \Illuminate\Http\UploadedFile $logo */
        $logo = $request->file('logo');
        $updated = $action->execute($centerModel, $logo, $admin instanceof User ? $admin : null);

        return response()->json([
            'success' => true,
            'message' => 'Center logo updated successfully',
            'data' => new CenterResource($updated),
        ]);
    }

    /**
     * Show center settings.
     */
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

    /**
     * Update center settings.
     */
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

    /**
     * @param  array<int, int|string>  $centerIds
     * @return array<int, int>
     */
    private function uniqueCenterIds(array $centerIds): array
    {
        return array_values(array_unique(array_map('intval', $centerIds)));
    }
}
