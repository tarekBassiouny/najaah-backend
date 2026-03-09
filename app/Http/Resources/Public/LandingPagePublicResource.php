<?php

declare(strict_types=1);

namespace App\Http\Resources\Public;

use App\Models\CenterLandingPage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CenterLandingPage
 */
class LandingPagePublicResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var CenterLandingPage $landingPage */
        $landingPage = $this->resource;
        $center = $landingPage->center;

        return [
            'center' => [
                'id' => $center->id,
                'slug' => $center->slug,
                'name' => $center->translate('name'),
                'logo_url' => $center->logo_url,
            ],

            // Meta section
            'meta' => [
                'title' => $landingPage->meta_title,
                'description' => $landingPage->meta_description,
                'keywords' => $landingPage->meta_keywords,
            ],

            // Hero section (if visible)
            'hero' => $landingPage->show_hero ? [
                'title' => $landingPage->translate('hero_title'),
                'subtitle' => $landingPage->translate('hero_subtitle'),
                'background_url' => $landingPage->hero_background_url,
                'cta_text' => $landingPage->hero_cta_text,
                'cta_url' => $landingPage->hero_cta_url,
            ] : null,

            // About section (if visible)
            'about' => $landingPage->show_about ? [
                'title' => $landingPage->translate('about_title'),
                'content' => $landingPage->translate('about_content'),
                'image_url' => $landingPage->about_image_url,
            ] : null,

            // Contact section (if visible)
            'contact' => $landingPage->show_contact ? [
                'email' => $landingPage->contact_email,
                'phone' => $landingPage->contact_phone,
                'address' => $landingPage->contact_address,
            ] : null,

            // Social links
            'social' => [
                'facebook' => $landingPage->social_facebook,
                'twitter' => $landingPage->social_twitter,
                'instagram' => $landingPage->social_instagram,
                'youtube' => $landingPage->social_youtube,
                'linkedin' => $landingPage->social_linkedin,
                'tiktok' => $landingPage->social_tiktok,
            ],

            // Styling
            'styling' => [
                'primary_color' => $landingPage->primary_color ?? $center->primary_color,
                'secondary_color' => $landingPage->secondary_color,
                'font_family' => $landingPage->font_family,
            ],

            // Visibility flags
            'sections' => [
                'show_hero' => $landingPage->show_hero,
                'show_about' => $landingPage->show_about,
                'show_courses' => $landingPage->show_courses,
                'show_testimonials' => $landingPage->show_testimonials,
                'show_contact' => $landingPage->show_contact,
            ],

            // Testimonials (if visible and loaded)
            'testimonials' => $landingPage->show_testimonials
                ? $this->formatTestimonials($landingPage)
                : [],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function formatTestimonials(CenterLandingPage $landingPage): array
    {
        return $landingPage->testimonials
            ->filter(fn ($t) => $t->is_active)
            ->map(fn ($testimonial): array => [
                'id' => $testimonial->id,
                'author_name' => $testimonial->author_name,
                'author_title' => $testimonial->author_title,
                'author_image_url' => $testimonial->author_image_url,
                'content' => $testimonial->translate('content'),
                'rating' => $testimonial->rating,
            ])
            ->values()
            ->all();
    }
}
