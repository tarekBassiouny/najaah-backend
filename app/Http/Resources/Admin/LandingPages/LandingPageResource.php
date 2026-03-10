<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\LandingPages;

use App\Enums\LandingPageStatus;
use App\Models\CenterLandingPage;
use App\Services\LandingPages\LandingPageMediaUrlResolver;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CenterLandingPage
 */
class LandingPageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var CenterLandingPage $landingPage */
        $landingPage = $this->resource;
        $status = $landingPage->status instanceof LandingPageStatus ? $landingPage->status : LandingPageStatus::Draft;
        $mediaUrlResolver = app(LandingPageMediaUrlResolver::class);

        return [
            'id' => $landingPage->id,
            'center_id' => $landingPage->center_id,
            'status' => $status->value,
            'status_label' => $this->resolveStatusLabel($status),
            'is_published' => $landingPage->isPublished(),

            // Meta section
            'meta' => [
                'title' => $landingPage->meta_title,
                'description' => $landingPage->meta_description,
                'keywords' => $landingPage->meta_keywords,
            ],

            // Hero section
            'hero' => [
                'title' => $landingPage->translate('hero_title'),
                'title_translations' => $landingPage->hero_title_translations,
                'subtitle' => $landingPage->translate('hero_subtitle'),
                'subtitle_translations' => $landingPage->hero_subtitle_translations,
                'background_url' => $mediaUrlResolver->resolve($landingPage->hero_background_url),
                'cta_text' => $landingPage->hero_cta_text,
                'cta_url' => $landingPage->hero_cta_url,
            ],

            // About section
            'about' => [
                'title' => $landingPage->translate('about_title'),
                'title_translations' => $landingPage->about_title_translations,
                'content' => $landingPage->translate('about_content'),
                'content_translations' => $landingPage->about_content_translations,
                'image_url' => $mediaUrlResolver->resolve($landingPage->about_image_url),
            ],

            // Contact section
            'contact' => [
                'email' => $landingPage->contact_email,
                'phone' => $landingPage->contact_phone,
                'address' => $landingPage->contact_address,
            ],

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
                'primary_color' => $landingPage->primary_color,
                'secondary_color' => $landingPage->secondary_color,
                'font_family' => $landingPage->font_family,
            ],

            // Visibility
            'visibility' => [
                'show_hero' => $landingPage->show_hero,
                'show_about' => $landingPage->show_about,
                'show_courses' => $landingPage->show_courses,
                'show_testimonials' => $landingPage->show_testimonials,
                'show_contact' => $landingPage->show_contact,
            ],

            // Testimonials
            'testimonials' => LandingTestimonialResource::collection($this->whenLoaded('testimonials')),

            'created_at' => $landingPage->created_at?->toISOString(),
            'updated_at' => $landingPage->updated_at?->toISOString(),
        ];
    }

    private function resolveStatusLabel(LandingPageStatus $status): string
    {
        return match ($status) {
            LandingPageStatus::Published => 'Published',
            default => 'Draft',
        };
    }
}
