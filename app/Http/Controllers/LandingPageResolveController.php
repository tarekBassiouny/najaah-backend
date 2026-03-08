<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\Public\LandingPagePublicResource;
use App\Models\Center;
use App\Services\LandingPages\Contracts\LandingPageServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LandingPageResolveController extends Controller
{
    public function __construct(
        private readonly LandingPageServiceInterface $landingPageService,
    ) {}

    /**
     * Resolve landing page by center slug.
     *
     * Returns the published landing page for the given center slug.
     * If a valid preview_token is provided, returns the draft version.
     */
    public function show(Request $request, string $slug): JsonResponse
    {
        $center = Center::where('slug', $slug)->first();

        if (! $center instanceof Center) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Center not found',
                ],
            ], 404);
        }

        $previewToken = $request->query('preview_token');
        $landingPage = $center->landingPage;

        if ($landingPage === null) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Landing page not found',
                ],
            ], 404);
        }

        // Check if preview mode with valid token
        $isPreviewMode = false;
        if (is_string($previewToken) && $previewToken !== '') {
            $isPreviewMode = $this->landingPageService->validatePreviewToken($landingPage, $previewToken);
        }

        // If not preview mode, check if published
        if (! $isPreviewMode && ! $landingPage->isPublished()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Landing page not found',
                ],
            ], 404);
        }

        // Load testimonials
        $landingPage->load(['testimonials' => function ($query): void {
            $query->where('is_active', true)->orderBy('sort_order');
        }]);

        return response()->json([
            'success' => true,
            'message' => 'Landing page retrieved successfully',
            'data' => new LandingPagePublicResource($landingPage),
            'meta' => [
                'is_preview' => $isPreviewMode,
            ],
        ]);
    }
}
