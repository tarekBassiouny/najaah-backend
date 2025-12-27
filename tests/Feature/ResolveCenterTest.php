<?php

declare(strict_types=1);

use App\Models\Center;
use App\Models\CenterSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('public-centers');

beforeEach(function (): void {
    config()->set('services.system_api_key', 'system-test-key');
});

it('returns public center discovery data', function (): void {
    $center = Center::factory()->create([
        'slug' => 'alpha-center',
        'type' => 1,
        'tier' => Center::TIER_PREMIUM,
        'onboarding_status' => Center::ONBOARDING_ACTIVE,
        'api_key' => 'center-key',
        'logo_url' => 'https://example.com/logo.png',
        'primary_color' => '#112233',
    ]);

    CenterSetting::factory()->create([
        'center_id' => $center->id,
        'settings' => [
            'branding' => [
                'primary_color' => '#445566',
            ],
        ],
    ]);

    $response = $this->getJson('/api/v1/resolve/centers/alpha-center', [
        'X-Api-Key' => 'center-key',
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.slug', 'alpha-center')
        ->assertJsonPath('data.type', 'branded')
        ->assertJsonPath('data.tier', 'premium')
        ->assertJsonPath('data.branding.logo_url', 'https://example.com/logo.png')
        ->assertJsonPath('data.branding.primary_color', '#445566');
    $response->assertJsonMissing(['api_key' => 'center-key']);
});

it('returns unbranded center data', function (): void {
    $center = Center::factory()->create([
        'slug' => 'unbranded-center',
        'type' => 0,
        'tier' => Center::TIER_STANDARD,
        'onboarding_status' => Center::ONBOARDING_ACTIVE,
        'api_key' => 'unbranded-key',
        'logo_url' => 'https://example.com/unbranded.png',
        'primary_color' => '#223344',
    ]);

    $response = $this->getJson('/api/v1/resolve/centers/unbranded-center', [
        'X-Api-Key' => 'unbranded-key',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.id', $center->id)
        ->assertJsonPath('data.type', 'unbranded')
        ->assertJsonPath('data.tier', 'standard')
        ->assertJsonPath('data.branding.logo_url', 'https://example.com/unbranded.png')
        ->assertJsonPath('data.branding.primary_color', '#223344');
});

it('rejects invalid api key', function (): void {
    Center::factory()->create([
        'slug' => 'alpha-center',
        'type' => 1,
        'onboarding_status' => Center::ONBOARDING_ACTIVE,
        'api_key' => 'center-key',
    ]);

    $response = $this->getJson('/api/v1/resolve/centers/alpha-center', [
        'X-Api-Key' => 'invalid-key',
    ]);

    $response->assertStatus(401)
        ->assertJsonPath('success', false)
        ->assertJsonPath('error.code', 'INVALID_API_KEY');
});

it('rejects api key for another center', function (): void {
    Center::factory()->create([
        'slug' => 'alpha-center',
        'type' => 1,
        'onboarding_status' => Center::ONBOARDING_ACTIVE,
        'api_key' => 'alpha-key',
    ]);
    Center::factory()->create([
        'slug' => 'beta-center',
        'type' => 0,
        'onboarding_status' => Center::ONBOARDING_ACTIVE,
        'api_key' => 'beta-key',
    ]);

    $response = $this->getJson('/api/v1/resolve/centers/alpha-center', [
        'X-Api-Key' => 'beta-key',
    ]);

    $response->assertStatus(403)
        ->assertJsonPath('success', false)
        ->assertJsonPath('error.code', 'CENTER_MISMATCH');
});

it('rejects inactive centers', function (): void {
    Center::factory()->create([
        'slug' => 'alpha-center',
        'type' => 1,
        'onboarding_status' => Center::ONBOARDING_FAILED,
        'api_key' => 'alpha-key',
    ]);

    $response = $this->getJson('/api/v1/resolve/centers/alpha-center', [
        'X-Api-Key' => 'alpha-key',
    ]);

    $response->assertStatus(403)
        ->assertJsonPath('success', false)
        ->assertJsonPath('error.code', 'CENTER_INACTIVE');
});

it('returns not found for unknown center slug', function (): void {
    Center::factory()->create([
        'slug' => 'alpha-center',
        'type' => 1,
        'onboarding_status' => Center::ONBOARDING_ACTIVE,
        'api_key' => 'alpha-key',
    ]);

    $response = $this->getJson('/api/v1/resolve/centers/missing-center', [
        'X-Api-Key' => 'alpha-key',
    ]);

    $response->assertStatus(404)
        ->assertJsonPath('success', false)
        ->assertJsonPath('error.code', 'NOT_FOUND');
});

it('does not expose center resolve under admin routes', function (): void {
    $response = $this->getJson('/api/v1/admin/centers/alpha-center');

    $response->assertStatus(404);
});
