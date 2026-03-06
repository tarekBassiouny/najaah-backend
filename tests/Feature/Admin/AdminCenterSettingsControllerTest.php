<?php

declare(strict_types=1);

use App\Models\Center;
use App\Models\CenterSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('center-settings', 'admin');

it('returns center settings', function (): void {
    $center = Center::factory()->create();
    CenterSetting::factory()->create([
        'center_id' => $center->id,
        'settings' => [
            'default_view_limit' => 2,
            'allow_extra_view_requests' => true,
            'pdf_download_permission' => false,
        ],
    ]);

    $this->asAdmin();
    $response = $this->getJson("/api/v1/admin/centers/{$center->id}/settings", $this->adminHeaders());

    $response
        ->assertOk()
        ->assertJsonPath('data.settings.default_view_limit', 2)
        ->assertJsonPath('data.center_id', $center->id);
});

it('updates center settings with partial payload', function (): void {
    $center = Center::factory()->create();
    CenterSetting::factory()->create([
        'center_id' => $center->id,
        'settings' => [
            'default_view_limit' => 2,
            'allow_extra_view_requests' => true,
            'pdf_download_permission' => false,
        ],
    ]);

    $this->asAdmin();
    $response = $this->patchJson("/api/v1/admin/centers/{$center->id}/settings", [
        'settings' => [
            'pdf_download_permission' => true,
        ],
    ], $this->adminHeaders());

    $response
        ->assertOk()
        ->assertJsonPath('data.settings.pdf_download_permission', true);

    $this->assertDatabaseHas('center_settings', [
        'center_id' => $center->id,
        'settings->pdf_download_permission' => true,
    ]);
});

it('updates education profile center settings', function (): void {
    $center = Center::factory()->create();

    $this->asAdmin();
    $response = $this->patchJson("/api/v1/admin/centers/{$center->id}/settings", [
        'settings' => [
            'education_profile' => [
                'enable_grade' => true,
                'enable_school' => false,
                'enable_college' => true,
                'require_grade' => true,
                'require_school' => false,
                'require_college' => false,
            ],
        ],
    ], $this->adminHeaders());

    $response
        ->assertOk()
        ->assertJsonPath('data.settings.education_profile.enable_grade', true)
        ->assertJsonPath('data.settings.education_profile.enable_school', false)
        ->assertJsonPath('data.settings.education_profile.require_grade', true);

    $this->assertDatabaseHas('center_settings', [
        'center_id' => $center->id,
        'settings->education_profile->enable_school' => false,
        'settings->education_profile->require_grade' => true,
    ]);
});

it('rejects unsupported setting keys', function (): void {
    $center = Center::factory()->create();

    $this->asAdmin();
    $response = $this->patchJson("/api/v1/admin/centers/{$center->id}/settings", [
        'settings' => [
            'unknown_key' => 5,
        ],
    ], $this->adminHeaders());

    $response->assertStatus(422)->assertJsonPath('error.code', 'VALIDATION_ERROR');
});

it('rejects unsupported education profile setting keys', function (): void {
    $center = Center::factory()->create();

    $this->asAdmin();
    $response = $this->patchJson("/api/v1/admin/centers/{$center->id}/settings", [
        'settings' => [
            'education_profile' => [
                'unknown_toggle' => true,
            ],
        ],
    ], $this->adminHeaders());

    $response->assertStatus(422)->assertJsonPath('error.code', 'VALIDATION_ERROR');
});

it('requires authentication', function (): void {
    $center = Center::factory()->create();

    $response = $this->getJson("/api/v1/admin/centers/{$center->id}/settings");

    $response->assertStatus(401);
});
