<?php

declare(strict_types=1);

use App\Enums\LandingPageStatus;
use App\Models\Center;
use App\Models\CenterLandingPage;
use App\Models\CenterLandingTestimonial;
use App\Services\LandingPages\Contracts\LandingPageServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('landing-pages', 'public');

it('returns published landing page by center slug', function (): void {
    $center = Center::factory()->create(['slug' => 'test-center']);
    CenterLandingPage::factory()->create([
        'center_id' => $center->id,
        'status' => LandingPageStatus::Published,
        'meta_title' => 'Test Title',
        'hero_title_translations' => ['en' => 'Welcome', 'ar' => 'مرحبا'],
    ]);

    $response = $this->getJson('/api/v1/resolve/landing-page/test-center');

    $response
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.center.slug', 'test-center')
        ->assertJsonPath('data.meta.title', 'Test Title');
});

it('returns 404 for unpublished landing page without preview token', function (): void {
    $center = Center::factory()->create(['slug' => 'draft-center']);
    CenterLandingPage::factory()->create([
        'center_id' => $center->id,
        'status' => LandingPageStatus::Draft,
    ]);

    $response = $this->getJson('/api/v1/resolve/landing-page/draft-center');

    $response
        ->assertNotFound()
        ->assertJsonPath('error.code', 'NOT_FOUND');
});

it('returns draft landing page with valid preview token', function (): void {
    $center = Center::factory()->create(['slug' => 'preview-center']);
    $landingPage = CenterLandingPage::factory()->create([
        'center_id' => $center->id,
        'status' => LandingPageStatus::Draft,
        'meta_title' => 'Preview Title',
    ]);

    $landingPageService = app(LandingPageServiceInterface::class);
    $token = $landingPageService->generatePreviewToken($landingPage);

    $response = $this->getJson("/api/v1/resolve/landing-page/preview-center?preview_token={$token}");

    $response
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('meta.is_preview', true)
        ->assertJsonPath('data.meta.title', 'Preview Title');
});

it('returns 404 for non-existent center', function (): void {
    $response = $this->getJson('/api/v1/resolve/landing-page/non-existent-center');

    $response
        ->assertNotFound()
        ->assertJsonPath('error.code', 'NOT_FOUND');
});

it('returns 404 for center without landing page', function (): void {
    Center::factory()->create(['slug' => 'no-landing']);

    $response = $this->getJson('/api/v1/resolve/landing-page/no-landing');

    $response
        ->assertNotFound()
        ->assertJsonPath('error.code', 'NOT_FOUND');
});

it('includes active testimonials in response', function (): void {
    $center = Center::factory()->create(['slug' => 'testimonials-center']);
    $landingPage = CenterLandingPage::factory()->create([
        'center_id' => $center->id,
        'status' => LandingPageStatus::Published,
        'show_testimonials' => true,
    ]);

    CenterLandingTestimonial::factory()->create([
        'center_landing_page_id' => $landingPage->id,
        'author_name' => 'Active Author',
        'is_active' => true,
        'sort_order' => 0,
    ]);

    CenterLandingTestimonial::factory()->create([
        'center_landing_page_id' => $landingPage->id,
        'author_name' => 'Inactive Author',
        'is_active' => false,
        'sort_order' => 1,
    ]);

    $response = $this->getJson('/api/v1/resolve/landing-page/testimonials-center');

    $response
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data.testimonials');
});

it('respects section visibility settings', function (): void {
    $center = Center::factory()->create(['slug' => 'visibility-center']);
    CenterLandingPage::factory()->create([
        'center_id' => $center->id,
        'status' => LandingPageStatus::Published,
        'show_hero' => true,
        'show_about' => false,
        'show_contact' => false,
        'hero_title_translations' => ['en' => 'Hero Title'],
        'about_title_translations' => ['en' => 'About Title'],
    ]);

    $response = $this->getJson('/api/v1/resolve/landing-page/visibility-center');

    $response
        ->assertOk()
        ->assertJsonPath('data.hero.title', 'Hero Title')
        ->assertJsonPath('data.about', null)
        ->assertJsonPath('data.contact', null);
});

it('returns layout metadata with defaults for legacy records', function (): void {
    $center = Center::factory()->create(['slug' => 'layout-defaults-center']);
    CenterLandingPage::factory()->create([
        'center_id' => $center->id,
        'status' => LandingPageStatus::Published,
        'section_order' => null,
        'section_layouts' => null,
        'section_styles' => null,
    ]);

    $response = $this->getJson('/api/v1/resolve/landing-page/layout-defaults-center');

    $response
        ->assertOk()
        ->assertJsonPath('data.layout.section_order.0', 'hero')
        ->assertJsonPath('data.layout.section_layouts.hero', 'default')
        ->assertJsonPath('data.layout.section_styles.hero', []);
});
