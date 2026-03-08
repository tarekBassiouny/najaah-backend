<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\LandingPages;

use App\Http\Controllers\Controller;
use App\Models\Center;
use App\Services\LandingPages\Contracts\LandingPageServiceInterface;
use App\Services\LandingPages\Contracts\SubdomainResolverServiceInterface;
use Illuminate\Http\JsonResponse;

class LandingPagePreviewController extends Controller
{
    public function __construct(
        private readonly LandingPageServiceInterface $landingPageService,
        private readonly SubdomainResolverServiceInterface $subdomainResolverService,
    ) {}

    /**
     * Generate a preview token for the landing page.
     */
    public function generateToken(int $center): JsonResponse
    {
        $centerModel = Center::find($center);

        if (! $centerModel instanceof Center) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Center not found',
                ],
            ], 404);
        }

        $landingPage = $this->landingPageService->getOrCreateForCenter($centerModel);
        $token = $this->landingPageService->generatePreviewToken($landingPage);

        $previewUrl = $this->subdomainResolverService->buildCenterUrl(
            $centerModel,
            sprintf('?preview_token=%s', $token)
        );

        return response()->json([
            'success' => true,
            'message' => 'Preview token generated successfully',
            'data' => [
                'token' => $token,
                'preview_url' => $previewUrl,
                'expires_in_minutes' => 30,
            ],
        ]);
    }
}
