<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\AI;

use App\Http\Controllers\Concerns\AdminAuthenticates;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AI\ListCenterAIOptionsRequest;
use App\Http\Requests\Admin\AI\UpdateCenterAIProviderRequest;
use App\Http\Requests\Admin\AI\UpdateSystemAIProviderRequest;
use App\Models\Center;
use App\Models\User;
use App\Services\AI\Contracts\AIIntegrationServiceInterface;
use App\Services\Centers\CenterScopeService;
use Illuminate\Http\JsonResponse;

class AIProviderConfigController extends Controller
{
    use AdminAuthenticates;

    public function __construct(
        private readonly AIIntegrationServiceInterface $aiIntegrationService,
        private readonly CenterScopeService $centerScopeService
    ) {}

    /**
     * List system AI providers and models.
     *
     * @group Admin - AI Integration
     */
    public function systemIndex(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->aiIntegrationService->listSystemProviders(),
        ]);
    }

    /**
     * Update system AI provider settings.
     *
     * @group Admin - AI Integration
     */
    public function systemUpdate(UpdateSystemAIProviderRequest $request, string $provider): JsonResponse
    {
        $admin = $this->requireAdmin();

        $config = $this->aiIntegrationService->updateSystemProvider(
            $provider,
            $request->validated(),
            $admin
        );

        $systemProvider = collect($this->aiIntegrationService->listSystemProviders())
            ->firstWhere('key', $config->provider_key);

        return response()->json([
            'success' => true,
            'message' => 'AI provider updated successfully.',
            'data' => is_array($systemProvider) ? $systemProvider : null,
        ]);
    }

    /**
     * List center AI provider settings.
     *
     * @group Admin - AI Integration
     */
    public function centerIndex(Center $center): JsonResponse
    {
        $admin = $this->requireAdmin();
        $this->centerScopeService->assertAdminSameCenter($admin, $center);

        return response()->json([
            'success' => true,
            'data' => $this->aiIntegrationService->listCenterProviders($center),
        ]);
    }

    /**
     * Update center AI provider settings.
     *
     * @group Admin - AI Integration
     */
    public function centerUpdate(UpdateCenterAIProviderRequest $request, Center $center, string $provider): JsonResponse
    {
        /** @var User $admin */
        $admin = $this->requireAdmin();

        $setting = $this->aiIntegrationService->updateCenterProvider(
            $center,
            $provider,
            $request->validated(),
            $admin
        );

        $providerData = collect($this->aiIntegrationService->listCenterProviders($center))
            ->firstWhere('key', $setting->provider_key);

        return response()->json([
            'success' => true,
            'message' => 'Center AI provider settings updated successfully.',
            'data' => is_array($providerData) ? $providerData : null,
        ]);
    }

    /**
     * Get effective center AI options for provider/model dropdown.
     *
     * @group Admin - AI Integration
     */
    public function options(ListCenterAIOptionsRequest $request, Center $center): JsonResponse
    {
        $admin = $this->requireAdmin();
        $this->centerScopeService->assertAdminSameCenter($admin, $center);

        $enabledOnly = (bool) $request->boolean('enabled_only', false);

        return response()->json([
            'success' => true,
            'data' => $this->aiIntegrationService->getCenterOptions($center, $enabledOnly),
        ]);
    }
}
