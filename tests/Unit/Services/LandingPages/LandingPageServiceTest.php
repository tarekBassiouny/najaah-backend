<?php

declare(strict_types=1);

use App\Enums\LandingPageStatus;
use App\Models\Center;
use App\Models\CenterLandingPage;
use App\Models\CenterLandingTestimonial;
use App\Services\LandingPages\LandingPageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class)->group('landing-pages', 'unit');

beforeEach(function (): void {
    $this->service = app(LandingPageService::class);
});

it('creates landing page when none exists', function (): void {
    $center = Center::factory()->create();

    $landingPage = $this->service->getOrCreateForCenter($center);

    expect($landingPage)->toBeInstanceOf(CenterLandingPage::class)
        ->and($landingPage->center_id)->toBe($center->id)
        ->and($landingPage->status)->toBe(LandingPageStatus::Draft);
});

it('returns existing landing page', function (): void {
    $center = Center::factory()->create();
    $existing = CenterLandingPage::factory()->create([
        'center_id' => $center->id,
        'meta_title' => 'Existing Page',
    ]);

    $landingPage = $this->service->getOrCreateForCenter($center);

    expect($landingPage->id)->toBe($existing->id)
        ->and($landingPage->meta_title)->toBe('Existing Page');
});

it('publishes landing page', function (): void {
    $center = Center::factory()->create();
    $landingPage = CenterLandingPage::factory()->create([
        'center_id' => $center->id,
        'status' => LandingPageStatus::Draft,
    ]);

    $updated = $this->service->publish($landingPage);

    expect($updated->status)->toBe(LandingPageStatus::Published)
        ->and($updated->isPublished())->toBeTrue();
});

it('unpublishes landing page', function (): void {
    $center = Center::factory()->create();
    $landingPage = CenterLandingPage::factory()->create([
        'center_id' => $center->id,
        'status' => LandingPageStatus::Published,
    ]);

    $updated = $this->service->unpublish($landingPage);

    expect($updated->status)->toBe(LandingPageStatus::Draft)
        ->and($updated->isPublished())->toBeFalse();
});

it('updates meta section', function (): void {
    $center = Center::factory()->create();
    $landingPage = CenterLandingPage::factory()->create(['center_id' => $center->id]);

    $updated = $this->service->updateMetaSection($landingPage, [
        'meta_title' => 'New Title',
        'meta_description' => 'New Description',
    ]);

    expect($updated->meta_title)->toBe('New Title')
        ->and($updated->meta_description)->toBe('New Description');
});

it('updates hero section with translations', function (): void {
    $center = Center::factory()->create();
    $landingPage = CenterLandingPage::factory()->create(['center_id' => $center->id]);

    $updated = $this->service->updateHeroSection($landingPage, [
        'hero_title' => ['en' => 'Welcome', 'ar' => 'مرحبا'],
        'hero_cta_text' => 'Get Started',
    ]);

    expect($updated->hero_title_translations)->toEqual(['en' => 'Welcome', 'ar' => 'مرحبا'])
        ->and($updated->hero_cta_text)->toBe('Get Started');
});

it('creates testimonial with auto-incrementing sort order', function (): void {
    $center = Center::factory()->create();
    $landingPage = CenterLandingPage::factory()->create(['center_id' => $center->id]);

    $t1 = $this->service->createTestimonial($landingPage, [
        'author_name' => 'Author 1',
        'content' => ['en' => 'Content 1'],
    ]);

    $t2 = $this->service->createTestimonial($landingPage, [
        'author_name' => 'Author 2',
        'content' => ['en' => 'Content 2'],
    ]);

    expect($t1->sort_order)->toBe(1)
        ->and($t2->sort_order)->toBe(2);
});

it('reorders testimonials', function (): void {
    $center = Center::factory()->create();
    $landingPage = CenterLandingPage::factory()->create(['center_id' => $center->id]);
    $t1 = CenterLandingTestimonial::factory()->create([
        'center_landing_page_id' => $landingPage->id,
        'sort_order' => 0,
    ]);
    $t2 = CenterLandingTestimonial::factory()->create([
        'center_landing_page_id' => $landingPage->id,
        'sort_order' => 1,
    ]);

    $this->service->reorderTestimonials($landingPage, [$t2->id, $t1->id]);

    $t1->refresh();
    $t2->refresh();

    expect($t2->sort_order)->toBe(0)
        ->and($t1->sort_order)->toBe(1);
});

it('generates and validates preview token', function (): void {
    $center = Center::factory()->create();
    $landingPage = CenterLandingPage::factory()->create(['center_id' => $center->id]);

    $token = $this->service->generatePreviewToken($landingPage);

    expect($token)->toBeString()
        ->and(strlen($token))->toBe(64)
        ->and($this->service->validatePreviewToken($landingPage, $token))->toBeTrue()
        ->and($this->service->validatePreviewToken($landingPage, 'invalid-token'))->toBeFalse();
});

it('returns published landing page for center', function (): void {
    $center = Center::factory()->create();
    CenterLandingPage::factory()->create([
        'center_id' => $center->id,
        'status' => LandingPageStatus::Published,
    ]);

    $landingPage = $this->service->getPublishedForCenter($center);

    expect($landingPage)->not->toBeNull()
        ->and($landingPage->isPublished())->toBeTrue();
});

it('returns null for unpublished landing page when getting published', function (): void {
    $center = Center::factory()->create();
    CenterLandingPage::factory()->create([
        'center_id' => $center->id,
        'status' => LandingPageStatus::Draft,
    ]);

    $landingPage = $this->service->getPublishedForCenter($center);

    expect($landingPage)->toBeNull();
});
