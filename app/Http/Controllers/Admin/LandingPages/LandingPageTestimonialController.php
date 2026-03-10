<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\LandingPages;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LandingPages\ReorderTestimonialsRequest;
use App\Http\Requests\Admin\LandingPages\StoreLandingTestimonialRequest;
use App\Http\Requests\Admin\LandingPages\UpdateLandingTestimonialRequest;
use App\Http\Resources\Admin\LandingPages\LandingPageResource;
use App\Http\Resources\Admin\LandingPages\LandingTestimonialResource;
use App\Models\Center;
use App\Models\CenterLandingTestimonial;
use App\Models\User;
use App\Services\LandingPages\Contracts\LandingPageServiceInterface;
use Illuminate\Http\JsonResponse;

class LandingPageTestimonialController extends Controller
{
    public function __construct(
        private readonly LandingPageServiceInterface $landingPageService,
    ) {}

    /**
     * List testimonials for a center's landing page.
     */
    public function index(int $center): JsonResponse
    {
        $centerModel = Center::find($center);

        if (! $centerModel instanceof Center) {
            return $this->notFoundResponse('Center not found');
        }

        $landingPage = $centerModel->landingPage;

        if ($landingPage === null) {
            return response()->json([
                'success' => true,
                'message' => 'No testimonials found',
                'data' => [],
            ]);
        }

        $testimonials = $landingPage->testimonials()->orderBy('sort_order')->get();

        return response()->json([
            'success' => true,
            'message' => 'Testimonials retrieved successfully',
            'data' => LandingTestimonialResource::collection($testimonials),
        ]);
    }

    /**
     * Create a new testimonial.
     */
    public function store(StoreLandingTestimonialRequest $request, int $center): JsonResponse
    {
        $centerModel = Center::find($center);

        if (! $centerModel instanceof Center) {
            return $this->notFoundResponse('Center not found');
        }

        $landingPage = $this->landingPageService->getOrCreateForCenter($centerModel);
        $admin = $request->user();

        /** @var array<string, mixed> $data */
        $data = $request->validated();
        $testimonial = $this->landingPageService->createTestimonial(
            $landingPage,
            $data,
            $admin instanceof User ? $admin : null
        );

        return response()->json([
            'success' => true,
            'message' => 'Testimonial created successfully',
            'data' => new LandingTestimonialResource($testimonial),
        ], 201);
    }

    /**
     * Show a single testimonial.
     */
    public function show(int $center, int $testimonial): JsonResponse
    {
        $centerModel = Center::find($center);

        if (! $centerModel instanceof Center) {
            return $this->notFoundResponse('Center not found');
        }

        $testimonialModel = CenterLandingTestimonial::where('id', $testimonial)
            ->whereHas('landingPage', function ($query) use ($centerModel): void {
                $query->where('center_id', $centerModel->id);
            })
            ->first();

        if (! $testimonialModel instanceof CenterLandingTestimonial) {
            return $this->notFoundResponse('Testimonial not found');
        }

        return response()->json([
            'success' => true,
            'message' => 'Testimonial retrieved successfully',
            'data' => new LandingTestimonialResource($testimonialModel),
        ]);
    }

    /**
     * Update a testimonial.
     */
    public function update(UpdateLandingTestimonialRequest $request, int $center, int $testimonial): JsonResponse
    {
        $centerModel = Center::find($center);

        if (! $centerModel instanceof Center) {
            return $this->notFoundResponse('Center not found');
        }

        $testimonialModel = CenterLandingTestimonial::where('id', $testimonial)
            ->whereHas('landingPage', function ($query) use ($centerModel): void {
                $query->where('center_id', $centerModel->id);
            })
            ->first();

        if (! $testimonialModel instanceof CenterLandingTestimonial) {
            return $this->notFoundResponse('Testimonial not found');
        }

        $admin = $request->user();

        /** @var array<string, mixed> $data */
        $data = $request->validated();
        $updated = $this->landingPageService->updateTestimonial(
            $testimonialModel,
            $data,
            $admin instanceof User ? $admin : null
        );

        return response()->json([
            'success' => true,
            'message' => 'Testimonial updated successfully',
            'data' => new LandingTestimonialResource($updated),
        ]);
    }

    /**
     * Delete a testimonial.
     */
    public function destroy(int $center, int $testimonial): JsonResponse
    {
        $centerModel = Center::find($center);

        if (! $centerModel instanceof Center) {
            return $this->notFoundResponse('Center not found');
        }

        $testimonialModel = CenterLandingTestimonial::where('id', $testimonial)
            ->whereHas('landingPage', function ($query) use ($centerModel): void {
                $query->where('center_id', $centerModel->id);
            })
            ->first();

        if (! $testimonialModel instanceof CenterLandingTestimonial) {
            return $this->notFoundResponse('Testimonial not found');
        }

        $admin = request()->user();
        $this->landingPageService->deleteTestimonial(
            $testimonialModel,
            $admin instanceof User ? $admin : null
        );

        return response()->json([
            'success' => true,
            'message' => 'Testimonial deleted successfully',
            'data' => null,
        ]);
    }

    /**
     * Reorder testimonials.
     */
    public function reorder(ReorderTestimonialsRequest $request, int $center): JsonResponse
    {
        $centerModel = Center::find($center);

        if (! $centerModel instanceof Center) {
            return $this->notFoundResponse('Center not found');
        }

        $landingPage = $centerModel->landingPage;

        if ($landingPage === null) {
            return $this->notFoundResponse('Landing page not found');
        }

        $admin = $request->user();
        $this->landingPageService->reorderTestimonials(
            $landingPage,
            $request->getOrderedIds(),
            $admin instanceof User ? $admin : null
        );

        $landingPage->refresh();
        $landingPage->load('testimonials');

        return response()->json([
            'success' => true,
            'message' => 'Testimonials reordered successfully',
            'data' => new LandingPageResource($landingPage),
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
