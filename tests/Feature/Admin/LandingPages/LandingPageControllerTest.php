<?php

declare(strict_types=1);

use App\Enums\CenterType;
use App\Enums\LandingPageStatus;
use App\Models\Center;
use App\Models\CenterLandingPage;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('landing-pages', 'admin');

it('returns landing page for center', function (): void {
    $center = Center::factory()->create(['type' => CenterType::Branded]);
    CenterLandingPage::factory()->create([
        'center_id' => $center->id,
        'meta_title' => 'Test Landing Page',
    ]);

    $this->asCenterAdmin($center);
    $response = $this->getJson("/api/v1/admin/centers/{$center->id}/landing-page", $this->adminHeaders());

    $response
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.center_id', $center->id)
        ->assertJsonPath('data.meta.title', 'Test Landing Page');
});

it('creates landing page when none exists', function (): void {
    $center = Center::factory()->create(['type' => CenterType::Branded]);

    $this->asCenterAdmin($center);
    $response = $this->getJson("/api/v1/admin/centers/{$center->id}/landing-page", $this->adminHeaders());

    $response
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.center_id', $center->id)
        ->assertJsonPath('data.status', LandingPageStatus::Draft->value);

    $this->assertDatabaseHas('center_landing_pages', [
        'center_id' => $center->id,
    ]);
});

it('publishes landing page', function (): void {
    $center = Center::factory()->create(['type' => CenterType::Branded]);
    CenterLandingPage::factory()->create([
        'center_id' => $center->id,
        'status' => LandingPageStatus::Draft,
    ]);

    $this->asCenterAdmin($center);
    $response = $this->postJson("/api/v1/admin/centers/{$center->id}/landing-page/publish", [], $this->adminHeaders());

    $response
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.status', LandingPageStatus::Published->value)
        ->assertJsonPath('data.is_published', true);

    $this->assertDatabaseHas('center_landing_pages', [
        'center_id' => $center->id,
        'status' => LandingPageStatus::Published->value,
    ]);
});

it('unpublishes landing page', function (): void {
    $center = Center::factory()->create(['type' => CenterType::Branded]);
    CenterLandingPage::factory()->create([
        'center_id' => $center->id,
        'status' => LandingPageStatus::Published,
    ]);

    $this->asCenterAdmin($center);
    $response = $this->postJson("/api/v1/admin/centers/{$center->id}/landing-page/unpublish", [], $this->adminHeaders());

    $response
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.status', LandingPageStatus::Draft->value)
        ->assertJsonPath('data.is_published', false);
});

it('returns 404 for non-existent center', function (): void {
    $this->asAdmin();
    $response = $this->getJson('/api/v1/admin/centers/99999/landing-page', $this->adminHeaders());

    $response
        ->assertNotFound()
        ->assertJsonPath('error.code', 'NOT_FOUND');
});

it('requires authentication', function (): void {
    $center = Center::factory()->create(['type' => CenterType::Branded]);

    $response = $this->getJson("/api/v1/admin/centers/{$center->id}/landing-page");

    $response->assertStatus(401);
});

it('blocks unbranded centers from landing page editor', function (): void {
    $center = Center::factory()->create([
        'type' => CenterType::Unbranded,
    ]);

    $this->asCenterAdmin($center);
    $response = $this->getJson("/api/v1/admin/centers/{$center->id}/landing-page", $this->adminHeaders());

    $response
        ->assertStatus(422)
        ->assertJsonPath('error.code', 'VALIDATION_ERROR')
        ->assertJsonPath('error.message', 'Landing pages are allowed only for branded centers.');
});
