<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Center;
use App\Services\LandingPages\Contracts\LandingPageServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LandingPageController extends Controller
{
    public function __construct(
        private readonly LandingPageServiceInterface $landingPageService,
    ) {}

    /**
     * Handle subdomain landing page request.
     *
     * This controller handles requests to {subdomain}.najaah.me/
     * and returns the landing page data for the center.
     */
    public function show(Request $request): JsonResponse
    {
        $center = $request->attributes->get('center');

        if (! $center instanceof Center) {
            // No center found for this subdomain
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
            // Center exists but has no landing page configured
            // Return basic center info for fallback
            return response()->json([
                'success' => true,
                'message' => 'Center found, no custom landing page',
                'data' => [
                    'center' => [
                        'id' => $center->id,
                        'slug' => $center->slug,
                        'name' => $center->translate('name'),
                        'logo_url' => $center->logo_url,
                        'primary_color' => $center->primary_color,
                    ],
                    'has_landing_page' => false,
                    'redirect_to_login' => true,
                ],
            ]);
        }

        // Check if preview mode with valid token
        $isPreviewMode = false;
        if (is_string($previewToken) && $previewToken !== '') {
            $isPreviewMode = $this->landingPageService->validatePreviewToken($landingPage, $previewToken);
        }

        // If not preview mode and not published, redirect to login
        if (! $isPreviewMode && ! $landingPage->isPublished()) {
            return response()->json([
                'success' => true,
                'message' => 'Landing page not published',
                'data' => [
                    'center' => [
                        'id' => $center->id,
                        'slug' => $center->slug,
                        'name' => $center->translate('name'),
                        'logo_url' => $center->logo_url,
                        'primary_color' => $center->primary_color,
                    ],
                    'has_landing_page' => true,
                    'is_published' => false,
                    'redirect_to_login' => true,
                ],
            ]);
        }

        // Load testimonials
        $landingPage->load(['testimonials' => function ($query): void {
            $query->where('is_active', true)->orderBy('sort_order');
        }]);

        return response()->json([
            'success' => true,
            'message' => 'Landing page retrieved successfully',
            'data' => [
                'center' => [
                    'id' => $center->id,
                    'slug' => $center->slug,
                    'name' => $center->translate('name'),
                    'logo_url' => $center->logo_url,
                ],
                'has_landing_page' => true,
                'is_published' => $landingPage->isPublished(),
                'is_preview' => $isPreviewMode,
                'redirect_to_login' => false,
                'landing_page' => $this->formatLandingPage($landingPage, $center),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatLandingPage(\App\Models\CenterLandingPage $landingPage, Center $center): array
    {
        return [
            'meta' => [
                'title' => $landingPage->meta_title,
                'description' => $landingPage->meta_description,
                'keywords' => $landingPage->meta_keywords,
            ],
            'hero' => $landingPage->show_hero ? [
                'title' => $landingPage->translate('hero_title'),
                'subtitle' => $landingPage->translate('hero_subtitle'),
                'background_url' => $landingPage->hero_background_url,
                'cta_text' => $landingPage->hero_cta_text,
                'cta_url' => $landingPage->hero_cta_url,
            ] : null,
            'about' => $landingPage->show_about ? [
                'title' => $landingPage->translate('about_title'),
                'content' => $landingPage->translate('about_content'),
                'image_url' => $landingPage->about_image_url,
            ] : null,
            'contact' => $landingPage->show_contact ? [
                'email' => $landingPage->contact_email,
                'phone' => $landingPage->contact_phone,
                'address' => $landingPage->contact_address,
            ] : null,
            'social' => [
                'facebook' => $landingPage->social_facebook,
                'twitter' => $landingPage->social_twitter,
                'instagram' => $landingPage->social_instagram,
                'youtube' => $landingPage->social_youtube,
                'linkedin' => $landingPage->social_linkedin,
                'tiktok' => $landingPage->social_tiktok,
            ],
            'styling' => [
                'primary_color' => $landingPage->primary_color ?? $center->primary_color,
                'secondary_color' => $landingPage->secondary_color,
                'font_family' => $landingPage->font_family,
            ],
            'sections' => [
                'show_hero' => $landingPage->show_hero,
                'show_about' => $landingPage->show_about,
                'show_courses' => $landingPage->show_courses,
                'show_testimonials' => $landingPage->show_testimonials,
                'show_contact' => $landingPage->show_contact,
            ],
            'testimonials' => $landingPage->show_testimonials
                ? $landingPage->testimonials->filter(fn ($t) => $t->is_active)->map(fn ($t) => [
                    'id' => $t->id,
                    'author_name' => $t->author_name,
                    'author_title' => $t->author_title,
                    'author_image_url' => $t->author_image_url,
                    'content' => $t->translate('content'),
                    'rating' => $t->rating,
                ])->values()->all()
                : [],
        ];
    }
}
