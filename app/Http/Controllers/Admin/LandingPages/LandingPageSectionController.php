<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\LandingPages;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LandingPages\UpdateAboutSectionRequest;
use App\Http\Requests\Admin\LandingPages\UpdateContactSectionRequest;
use App\Http\Requests\Admin\LandingPages\UpdateHeroSectionRequest;
use App\Http\Requests\Admin\LandingPages\UpdateMetaSectionRequest;
use App\Http\Requests\Admin\LandingPages\UpdateSocialSectionRequest;
use App\Http\Requests\Admin\LandingPages\UpdateStylingSectionRequest;
use App\Http\Requests\Admin\LandingPages\UpdateVisibilitySectionRequest;
use App\Http\Resources\Admin\LandingPages\LandingPageResource;
use App\Models\Center;
use App\Models\User;
use App\Services\LandingPages\Contracts\LandingPageServiceInterface;
use Illuminate\Http\JsonResponse;

class LandingPageSectionController extends Controller
{
    public function __construct(
        private readonly LandingPageServiceInterface $landingPageService,
    ) {}

    /**
     * Update meta section.
     */
    public function updateMeta(UpdateMetaSectionRequest $request, int $center): JsonResponse
    {
        $centerModel = Center::find($center);

        if (! $centerModel instanceof Center) {
            return $this->notFoundResponse('Center not found');
        }

        $landingPage = $this->landingPageService->getOrCreateForCenter($centerModel);
        $admin = $request->user();

        /** @var array<string, mixed> $data */
        $data = $request->validated();
        $updated = $this->landingPageService->updateMetaSection(
            $landingPage,
            $data,
            $admin instanceof User ? $admin : null
        );

        return response()->json([
            'success' => true,
            'message' => 'Meta section updated successfully',
            'data' => new LandingPageResource($updated),
        ]);
    }

    /**
     * Update hero section.
     */
    public function updateHero(UpdateHeroSectionRequest $request, int $center): JsonResponse
    {
        $centerModel = Center::find($center);

        if (! $centerModel instanceof Center) {
            return $this->notFoundResponse('Center not found');
        }

        $landingPage = $this->landingPageService->getOrCreateForCenter($centerModel);
        $admin = $request->user();

        /** @var array<string, mixed> $data */
        $data = $request->validated();
        $updated = $this->landingPageService->updateHeroSection(
            $landingPage,
            $data,
            $admin instanceof User ? $admin : null
        );

        return response()->json([
            'success' => true,
            'message' => 'Hero section updated successfully',
            'data' => new LandingPageResource($updated),
        ]);
    }

    /**
     * Update about section.
     */
    public function updateAbout(UpdateAboutSectionRequest $request, int $center): JsonResponse
    {
        $centerModel = Center::find($center);

        if (! $centerModel instanceof Center) {
            return $this->notFoundResponse('Center not found');
        }

        $landingPage = $this->landingPageService->getOrCreateForCenter($centerModel);
        $admin = $request->user();

        /** @var array<string, mixed> $data */
        $data = $request->validated();
        $updated = $this->landingPageService->updateAboutSection(
            $landingPage,
            $data,
            $admin instanceof User ? $admin : null
        );

        return response()->json([
            'success' => true,
            'message' => 'About section updated successfully',
            'data' => new LandingPageResource($updated),
        ]);
    }

    /**
     * Update contact section.
     */
    public function updateContact(UpdateContactSectionRequest $request, int $center): JsonResponse
    {
        $centerModel = Center::find($center);

        if (! $centerModel instanceof Center) {
            return $this->notFoundResponse('Center not found');
        }

        $landingPage = $this->landingPageService->getOrCreateForCenter($centerModel);
        $admin = $request->user();

        /** @var array<string, mixed> $data */
        $data = $request->validated();
        $updated = $this->landingPageService->updateContactSection(
            $landingPage,
            $data,
            $admin instanceof User ? $admin : null
        );

        return response()->json([
            'success' => true,
            'message' => 'Contact section updated successfully',
            'data' => new LandingPageResource($updated),
        ]);
    }

    /**
     * Update social section.
     */
    public function updateSocial(UpdateSocialSectionRequest $request, int $center): JsonResponse
    {
        $centerModel = Center::find($center);

        if (! $centerModel instanceof Center) {
            return $this->notFoundResponse('Center not found');
        }

        $landingPage = $this->landingPageService->getOrCreateForCenter($centerModel);
        $admin = $request->user();

        /** @var array<string, mixed> $data */
        $data = $request->validated();
        $updated = $this->landingPageService->updateSocialSection(
            $landingPage,
            $data,
            $admin instanceof User ? $admin : null
        );

        return response()->json([
            'success' => true,
            'message' => 'Social section updated successfully',
            'data' => new LandingPageResource($updated),
        ]);
    }

    /**
     * Update styling section.
     */
    public function updateStyling(UpdateStylingSectionRequest $request, int $center): JsonResponse
    {
        $centerModel = Center::find($center);

        if (! $centerModel instanceof Center) {
            return $this->notFoundResponse('Center not found');
        }

        $landingPage = $this->landingPageService->getOrCreateForCenter($centerModel);
        $admin = $request->user();

        /** @var array<string, mixed> $data */
        $data = $request->validated();
        $updated = $this->landingPageService->updateStylingSection(
            $landingPage,
            $data,
            $admin instanceof User ? $admin : null
        );

        return response()->json([
            'success' => true,
            'message' => 'Styling section updated successfully',
            'data' => new LandingPageResource($updated),
        ]);
    }

    /**
     * Update visibility section.
     */
    public function updateVisibility(UpdateVisibilitySectionRequest $request, int $center): JsonResponse
    {
        $centerModel = Center::find($center);

        if (! $centerModel instanceof Center) {
            return $this->notFoundResponse('Center not found');
        }

        $landingPage = $this->landingPageService->getOrCreateForCenter($centerModel);
        $admin = $request->user();

        /** @var array<string, mixed> $data */
        $data = $request->validated();
        $updated = $this->landingPageService->updateVisibilitySection(
            $landingPage,
            $data,
            $admin instanceof User ? $admin : null
        );

        return response()->json([
            'success' => true,
            'message' => 'Visibility section updated successfully',
            'data' => new LandingPageResource($updated),
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
