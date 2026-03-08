<?php

declare(strict_types=1);

namespace App\Services\LandingPages;

use App\Enums\LandingPageStatus;
use App\Models\Center;
use App\Models\CenterLandingPage;
use App\Models\CenterLandingTestimonial;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use App\Services\LandingPages\Contracts\LandingPageServiceInterface;
use App\Support\AuditActions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LandingPageService implements LandingPageServiceInterface
{
    private const PREVIEW_TOKEN_TTL_MINUTES = 30;

    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {}

    public function getOrCreateForCenter(Center $center): CenterLandingPage
    {
        $landingPage = $center->landingPage;

        if ($landingPage !== null) {
            return $landingPage->load('testimonials');
        }

        return DB::transaction(function () use ($center): CenterLandingPage {
            /** @var CenterLandingPage $landingPage */
            $landingPage = CenterLandingPage::create([
                'center_id' => $center->id,
                'status' => LandingPageStatus::Draft,
            ]);

            return $landingPage->load('testimonials');
        });
    }

    public function getForCenter(Center $center): ?CenterLandingPage
    {
        return $center->landingPage?->load('testimonials');
    }

    public function getPublishedForCenter(Center $center): ?CenterLandingPage
    {
        $landingPage = $center->landingPage;

        if ($landingPage === null || ! $landingPage->isPublished()) {
            return null;
        }

        return $landingPage->load(['testimonials' => function ($query): void {
            $query->where('is_active', true)->orderBy('sort_order');
        }]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateMetaSection(CenterLandingPage $landingPage, array $data, ?User $actor = null): CenterLandingPage
    {
        $landingPage->update([
            'meta_title' => $data['meta_title'] ?? $landingPage->meta_title,
            'meta_description' => $data['meta_description'] ?? $landingPage->meta_description,
            'meta_keywords' => $data['meta_keywords'] ?? $landingPage->meta_keywords,
        ]);

        $this->auditLogService->log($actor, $landingPage, AuditActions::LANDING_PAGE_UPDATED, [
            'section' => 'meta',
        ]);

        return $landingPage->fresh(['testimonials']) ?? $landingPage;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateHeroSection(CenterLandingPage $landingPage, array $data, ?User $actor = null): CenterLandingPage
    {
        $updateData = [];

        if (array_key_exists('hero_title', $data)) {
            $updateData['hero_title_translations'] = $data['hero_title'];
        }

        if (array_key_exists('hero_subtitle', $data)) {
            $updateData['hero_subtitle_translations'] = $data['hero_subtitle'];
        }

        if (array_key_exists('hero_background_url', $data)) {
            $updateData['hero_background_url'] = $data['hero_background_url'];
        }

        if (array_key_exists('hero_cta_text', $data)) {
            $updateData['hero_cta_text'] = $data['hero_cta_text'];
        }

        if (array_key_exists('hero_cta_url', $data)) {
            $updateData['hero_cta_url'] = $data['hero_cta_url'];
        }

        if (! empty($updateData)) {
            $landingPage->update($updateData);
        }

        $this->auditLogService->log($actor, $landingPage, AuditActions::LANDING_PAGE_UPDATED, [
            'section' => 'hero',
        ]);

        return $landingPage->fresh(['testimonials']) ?? $landingPage;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateAboutSection(CenterLandingPage $landingPage, array $data, ?User $actor = null): CenterLandingPage
    {
        $updateData = [];

        if (array_key_exists('about_title', $data)) {
            $updateData['about_title_translations'] = $data['about_title'];
        }

        if (array_key_exists('about_content', $data)) {
            $updateData['about_content_translations'] = $data['about_content'];
        }

        if (array_key_exists('about_image_url', $data)) {
            $updateData['about_image_url'] = $data['about_image_url'];
        }

        if (! empty($updateData)) {
            $landingPage->update($updateData);
        }

        $this->auditLogService->log($actor, $landingPage, AuditActions::LANDING_PAGE_UPDATED, [
            'section' => 'about',
        ]);

        return $landingPage->fresh(['testimonials']) ?? $landingPage;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateContactSection(CenterLandingPage $landingPage, array $data, ?User $actor = null): CenterLandingPage
    {
        $landingPage->update([
            'contact_email' => $data['contact_email'] ?? $landingPage->contact_email,
            'contact_phone' => $data['contact_phone'] ?? $landingPage->contact_phone,
            'contact_address' => $data['contact_address'] ?? $landingPage->contact_address,
        ]);

        $this->auditLogService->log($actor, $landingPage, AuditActions::LANDING_PAGE_UPDATED, [
            'section' => 'contact',
        ]);

        return $landingPage->fresh(['testimonials']) ?? $landingPage;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateSocialSection(CenterLandingPage $landingPage, array $data, ?User $actor = null): CenterLandingPage
    {
        $landingPage->update([
            'social_facebook' => $data['social_facebook'] ?? $landingPage->social_facebook,
            'social_twitter' => $data['social_twitter'] ?? $landingPage->social_twitter,
            'social_instagram' => $data['social_instagram'] ?? $landingPage->social_instagram,
            'social_youtube' => $data['social_youtube'] ?? $landingPage->social_youtube,
            'social_linkedin' => $data['social_linkedin'] ?? $landingPage->social_linkedin,
            'social_tiktok' => $data['social_tiktok'] ?? $landingPage->social_tiktok,
        ]);

        $this->auditLogService->log($actor, $landingPage, AuditActions::LANDING_PAGE_UPDATED, [
            'section' => 'social',
        ]);

        return $landingPage->fresh(['testimonials']) ?? $landingPage;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateStylingSection(CenterLandingPage $landingPage, array $data, ?User $actor = null): CenterLandingPage
    {
        $landingPage->update([
            'primary_color' => $data['primary_color'] ?? $landingPage->primary_color,
            'secondary_color' => $data['secondary_color'] ?? $landingPage->secondary_color,
            'font_family' => $data['font_family'] ?? $landingPage->font_family,
        ]);

        $this->auditLogService->log($actor, $landingPage, AuditActions::LANDING_PAGE_UPDATED, [
            'section' => 'styling',
        ]);

        return $landingPage->fresh(['testimonials']) ?? $landingPage;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateVisibilitySection(CenterLandingPage $landingPage, array $data, ?User $actor = null): CenterLandingPage
    {
        $landingPage->update([
            'show_hero' => $data['show_hero'] ?? $landingPage->show_hero,
            'show_about' => $data['show_about'] ?? $landingPage->show_about,
            'show_courses' => $data['show_courses'] ?? $landingPage->show_courses,
            'show_testimonials' => $data['show_testimonials'] ?? $landingPage->show_testimonials,
            'show_contact' => $data['show_contact'] ?? $landingPage->show_contact,
        ]);

        $this->auditLogService->log($actor, $landingPage, AuditActions::LANDING_PAGE_UPDATED, [
            'section' => 'visibility',
        ]);

        return $landingPage->fresh(['testimonials']) ?? $landingPage;
    }

    public function publish(CenterLandingPage $landingPage, ?User $actor = null): CenterLandingPage
    {
        $landingPage->update(['status' => LandingPageStatus::Published]);

        $this->auditLogService->log($actor, $landingPage, AuditActions::LANDING_PAGE_PUBLISHED);

        return $landingPage->fresh(['testimonials']) ?? $landingPage;
    }

    public function unpublish(CenterLandingPage $landingPage, ?User $actor = null): CenterLandingPage
    {
        $landingPage->update(['status' => LandingPageStatus::Draft]);

        $this->auditLogService->log($actor, $landingPage, AuditActions::LANDING_PAGE_UNPUBLISHED);

        return $landingPage->fresh(['testimonials']) ?? $landingPage;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createTestimonial(CenterLandingPage $landingPage, array $data, ?User $actor = null): CenterLandingTestimonial
    {
        $maxOrder = $landingPage->testimonials()->max('sort_order') ?? 0;

        /** @var CenterLandingTestimonial $testimonial */
        $testimonial = $landingPage->testimonials()->create([
            'author_name' => $data['author_name'],
            'author_title' => $data['author_title'] ?? null,
            'author_image_url' => $data['author_image_url'] ?? null,
            'content_translations' => $data['content'],
            'rating' => $data['rating'] ?? null,
            'sort_order' => $maxOrder + 1,
            'is_active' => $data['is_active'] ?? true,
        ]);

        $this->auditLogService->log($actor, $testimonial, AuditActions::TESTIMONIAL_CREATED);

        return $testimonial;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateTestimonial(CenterLandingTestimonial $testimonial, array $data, ?User $actor = null): CenterLandingTestimonial
    {
        $updateData = [];

        if (array_key_exists('author_name', $data)) {
            $updateData['author_name'] = $data['author_name'];
        }

        if (array_key_exists('author_title', $data)) {
            $updateData['author_title'] = $data['author_title'];
        }

        if (array_key_exists('author_image_url', $data)) {
            $updateData['author_image_url'] = $data['author_image_url'];
        }

        if (array_key_exists('content', $data)) {
            $updateData['content_translations'] = $data['content'];
        }

        if (array_key_exists('rating', $data)) {
            $updateData['rating'] = $data['rating'];
        }

        if (array_key_exists('is_active', $data)) {
            $updateData['is_active'] = $data['is_active'];
        }

        if (! empty($updateData)) {
            $testimonial->update($updateData);
        }

        $this->auditLogService->log($actor, $testimonial, AuditActions::TESTIMONIAL_UPDATED);

        return $testimonial->fresh() ?? $testimonial;
    }

    public function deleteTestimonial(CenterLandingTestimonial $testimonial, ?User $actor = null): void
    {
        $this->auditLogService->log($actor, $testimonial, AuditActions::TESTIMONIAL_DELETED);

        $testimonial->delete();
    }

    /**
     * @param  array<int>  $orderedIds
     */
    public function reorderTestimonials(CenterLandingPage $landingPage, array $orderedIds, ?User $actor = null): void
    {
        DB::transaction(function () use ($landingPage, $orderedIds): void {
            foreach ($orderedIds as $order => $id) {
                CenterLandingTestimonial::where('id', $id)
                    ->where('center_landing_page_id', $landingPage->id)
                    ->update(['sort_order' => $order]);
            }
        });

        $this->auditLogService->log($actor, $landingPage, AuditActions::TESTIMONIALS_REORDERED);
    }

    public function generatePreviewToken(CenterLandingPage $landingPage): string
    {
        $token = Str::random(64);
        $cacheKey = $this->previewTokenCacheKey($landingPage->id, $token);

        Cache::put($cacheKey, true, now()->addMinutes(self::PREVIEW_TOKEN_TTL_MINUTES));

        return $token;
    }

    public function validatePreviewToken(CenterLandingPage $landingPage, string $token): bool
    {
        $cacheKey = $this->previewTokenCacheKey($landingPage->id, $token);

        return Cache::has($cacheKey);
    }

    private function previewTokenCacheKey(int $landingPageId, string $token): string
    {
        return sprintf('landing_page_preview:%d:%s', $landingPageId, $token);
    }
}
