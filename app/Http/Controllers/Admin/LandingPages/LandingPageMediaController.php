<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\LandingPages;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\LandingPages\LandingPageResource;
use App\Models\Center;
use App\Models\User;
use App\Services\LandingPages\Contracts\LandingPageServiceInterface;
use App\Services\Storage\Contracts\StorageServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LandingPageMediaController extends Controller
{
    public function __construct(
        private readonly LandingPageServiceInterface $landingPageService,
        private readonly StorageServiceInterface $storageService,
    ) {}

    /**
     * Upload hero background image.
     */
    public function uploadHeroBackground(Request $request, int $center): JsonResponse
    {
        $centerModel = Center::find($center);

        if (! $centerModel instanceof Center) {
            return $this->notFoundResponse('Center not found');
        }

        $request->validate([
            'image' => ['required', 'image', 'max:5120'], // 5MB max
        ]);

        $file = $request->file('image');

        if ($file === null) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Image file is required',
                ],
            ], 422);
        }

        $path = sprintf('centers/%d/landing-page/hero', $centerModel->id);
        $filename = sprintf('hero_%s.%s', now()->timestamp, $file->extension());

        $url = $this->storageService->upload($file, $path, $filename);

        $landingPage = $this->landingPageService->getOrCreateForCenter($centerModel);
        $admin = $request->user();

        $updated = $this->landingPageService->updateHeroSection(
            $landingPage,
            ['hero_background_url' => $url],
            $admin instanceof User ? $admin : null
        );

        return response()->json([
            'success' => true,
            'message' => 'Hero background uploaded successfully',
            'data' => [
                'url' => $url,
                'landing_page' => new LandingPageResource($updated),
            ],
        ]);
    }

    /**
     * Upload about section image.
     */
    public function uploadAboutImage(Request $request, int $center): JsonResponse
    {
        $centerModel = Center::find($center);

        if (! $centerModel instanceof Center) {
            return $this->notFoundResponse('Center not found');
        }

        $request->validate([
            'image' => ['required', 'image', 'max:5120'], // 5MB max
        ]);

        $file = $request->file('image');

        if ($file === null) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Image file is required',
                ],
            ], 422);
        }

        $path = sprintf('centers/%d/landing-page/about', $centerModel->id);
        $filename = sprintf('about_%s.%s', now()->timestamp, $file->extension());

        $url = $this->storageService->upload($file, $path, $filename);

        $landingPage = $this->landingPageService->getOrCreateForCenter($centerModel);
        $admin = $request->user();

        $updated = $this->landingPageService->updateAboutSection(
            $landingPage,
            ['about_image_url' => $url],
            $admin instanceof User ? $admin : null
        );

        return response()->json([
            'success' => true,
            'message' => 'About image uploaded successfully',
            'data' => [
                'url' => $url,
                'landing_page' => new LandingPageResource($updated),
            ],
        ]);
    }

    /**
     * Upload testimonial author image.
     */
    public function uploadTestimonialImage(Request $request, int $center): JsonResponse
    {
        $centerModel = Center::find($center);

        if (! $centerModel instanceof Center) {
            return $this->notFoundResponse('Center not found');
        }

        $request->validate([
            'image' => ['required', 'image', 'max:2048'], // 2MB max
        ]);

        $file = $request->file('image');

        if ($file === null) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Image file is required',
                ],
            ], 422);
        }

        $path = sprintf('centers/%d/landing-page/testimonials', $centerModel->id);
        $filename = sprintf('author_%s.%s', now()->timestamp, $file->extension());

        $url = $this->storageService->upload($file, $path, $filename);

        return response()->json([
            'success' => true,
            'message' => 'Testimonial image uploaded successfully',
            'data' => [
                'url' => $url,
            ],
        ]);
    }

    private function notFoundResponse(string $message): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => 'NOT_FOUND',
                'message' => $message,
            ],
        ], 404);
    }
}
