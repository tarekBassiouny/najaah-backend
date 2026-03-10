<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\LandingPages;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\LandingPages\LandingPageResource;
use App\Models\Center;
use App\Models\User;
use App\Services\LandingPages\Contracts\LandingPageServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LandingPageController extends Controller
{
    public function __construct(
        private readonly LandingPageServiceInterface $landingPageService,
    ) {}

    /**
     * Get or create landing page for a center.
     */
    public function show(int $center): JsonResponse
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

        return response()->json([
            'success' => true,
            'message' => 'Landing page retrieved successfully',
            'data' => new LandingPageResource($landingPage),
        ]);
    }

    /**
     * Publish the landing page.
     */
    public function publish(Request $request, int $center): JsonResponse
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

        $landingPage = $centerModel->landingPage;

        if ($landingPage === null) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Landing page not found',
                ],
            ], 404);
        }

        $admin = $request->user();
        $updated = $this->landingPageService->publish(
            $landingPage,
            $admin instanceof User ? $admin : null
        );

        return response()->json([
            'success' => true,
            'message' => 'Landing page published successfully',
            'data' => new LandingPageResource($updated),
        ]);
    }

    /**
     * Unpublish the landing page.
     */
    public function unpublish(Request $request, int $center): JsonResponse
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

        $landingPage = $centerModel->landingPage;

        if ($landingPage === null) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Landing page not found',
                ],
            ], 404);
        }

        $admin = $request->user();
        $updated = $this->landingPageService->unpublish(
            $landingPage,
            $admin instanceof User ? $admin : null
        );

        return response()->json([
            'success' => true,
            'message' => 'Landing page unpublished successfully',
            'data' => new LandingPageResource($updated),
        ]);
    }
}
