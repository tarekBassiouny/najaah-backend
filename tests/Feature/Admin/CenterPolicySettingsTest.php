<?php

declare(strict_types=1);

use App\Models\Center;
use App\Models\SystemSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('center-settings', 'admin');

beforeEach(function (): void {
    $this->asAdmin();
});

it('returns resolved center policy with system fallbacks', function (): void {
    $center = Center::factory()->create([
        'default_view_limit' => 4,
        'allow_extra_view_requests' => false,
        'pdf_download_permission' => true,
        'device_limit' => 2,
    ]);

    SystemSetting::query()->updateOrCreate([
        'key' => 'timezone',
    ], [
        'key' => 'timezone',
        'value' => ['timezone' => 'Africa/Cairo'],
        'is_public' => true,
    ]);
    SystemSetting::query()->updateOrCreate([
        'key' => 'support_email',
    ], [
        'key' => 'support_email',
        'value' => ['email' => 'ops@example.com'],
        'is_public' => true,
    ]);

    $response = $this->getJson("/api/v1/admin/centers/{$center->id}/settings", $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('data.settings.default_view_limit', 4)
        ->assertJsonPath('data.resolved_settings.default_view_limit', 4)
        ->assertJsonPath('data.resolved_settings.pdf_download_permission', true)
        ->assertJsonPath('data.resolved_settings.education_profile.enable_grade', true)
        ->assertJsonPath('data.resolved_settings.education_profile.enable_school', true)
        ->assertJsonPath('data.resolved_settings.education_profile.enable_college', true)
        ->assertJsonPath('data.resolved_settings.education_profile.enable_parent_phone', true)
        ->assertJsonPath('data.resolved_settings.education_profile.require_parent_phone', false)
        ->assertJsonPath('data.resolved_settings.timezone', 'Africa/Cairo')
        ->assertJsonPath('data.resolved_settings.support_email', 'ops@example.com')
        ->assertJsonPath('data.system_defaults.timezone', 'Africa/Cairo')
        ->assertJsonPath('data.catalog.timezone.scope', 'system');
});

it('syncs center columns when center settings are updated', function (): void {
    $center = Center::factory()->create([
        'pdf_download_permission' => false,
        'device_limit' => 1,
    ]);

    $response = $this->patchJson("/api/v1/admin/centers/{$center->id}/settings", [
        'settings' => [
            'pdf_download_permission' => true,
            'device_limit' => 3,
            'branding' => [
                'primary_color' => '#123456',
            ],
        ],
    ], $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('data.settings.pdf_download_permission', true)
        ->assertJsonPath('data.resolved_settings.device_limit', 3);

    $this->assertDatabaseHas('centers', [
        'id' => $center->id,
        'pdf_download_permission' => 1,
        'device_limit' => 3,
        'primary_color' => '#123456',
    ]);
});

it('enforces system limit from catalog via systemConstraints', function (): void {
    $center = Center::factory()->create([
        'default_view_limit' => 20,
        'device_limit' => 10,
    ]);

    SystemSetting::query()->updateOrCreate(
        ['key' => 'max_view_limit'],
        ['key' => 'max_view_limit', 'value' => ['value' => 5], 'is_public' => false],
    );
    SystemSetting::query()->updateOrCreate(
        ['key' => 'max_device_limit'],
        ['key' => 'max_device_limit', 'value' => ['value' => 2], 'is_public' => false],
    );

    $response = $this->getJson("/api/v1/admin/centers/{$center->id}/settings", $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('data.resolved_settings.default_view_limit', 5)
        ->assertJsonPath('data.resolved_settings.device_limit', 2)
        ->assertJsonPath('data.system_constraints.max_view_limit', 5)
        ->assertJsonPath('data.system_constraints.max_device_limit', 2);
});

it('enforces system override from catalog via applyCenterGovernance', function (): void {
    $center = Center::factory()->create([
        'allow_extra_view_requests' => true,
        'pdf_download_permission' => true,
        'allow_guest_browsing' => true,
    ]);

    $center->setting()->create([
        'settings' => [
            'allow_extra_view_requests' => true,
            'pdf_download_permission' => true,
            'allow_guest_browsing' => true,
        ],
    ]);

    SystemSetting::query()->updateOrCreate(
        ['key' => 'force_disable_extra_view_requests'],
        ['key' => 'force_disable_extra_view_requests', 'value' => ['enabled' => true], 'is_public' => false],
    );
    SystemSetting::query()->updateOrCreate(
        ['key' => 'force_disable_pdf_download'],
        ['key' => 'force_disable_pdf_download', 'value' => ['enabled' => true], 'is_public' => false],
    );
    SystemSetting::query()->updateOrCreate(
        ['key' => 'force_disable_guest_browsing'],
        ['key' => 'force_disable_guest_browsing', 'value' => ['enabled' => true], 'is_public' => false],
    );

    $response = $this->getJson("/api/v1/admin/centers/{$center->id}/settings", $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('data.resolved_settings.allow_extra_view_requests', false)
        ->assertJsonPath('data.resolved_settings.pdf_download_permission', false)
        ->assertJsonPath('data.resolved_settings.allow_guest_browsing', false);
});

it('rejects center setting that exceeds catalog-driven system limit', function (): void {
    $center = Center::factory()->create();

    SystemSetting::query()->updateOrCreate(
        ['key' => 'max_view_limit'],
        ['key' => 'max_view_limit', 'value' => ['value' => 5], 'is_public' => false],
    );

    $response = $this->patchJson("/api/v1/admin/centers/{$center->id}/settings", [
        'settings' => [
            'default_view_limit' => 10,
        ],
    ], $this->adminHeaders());

    $response->assertStatus(422);
});

it('includes feature_groups section in settings API response', function (): void {
    $center = Center::factory()->create();

    $response = $this->getJson("/api/v1/admin/centers/{$center->id}/settings", $this->adminHeaders());

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'sections' => [
                    'feature_groups' => [
                        'guest_browsing' => ['feature_flag', 'flag_enabled', 'center_settings', 'system_limits', 'system_overrides'],
                        'pdf_downloads' => ['feature_flag', 'flag_enabled', 'center_settings', 'system_limits', 'system_overrides'],
                        'codes_access' => ['feature_flag', 'flag_enabled', 'center_settings', 'system_limits', 'system_overrides'],
                    ],
                ],
            ],
        ])
        ->assertJsonPath('data.sections.feature_groups.guest_browsing.feature_flag', 'guest_browsing')
        ->assertJsonPath('data.sections.feature_groups.guest_browsing.flag_enabled', true)
        ->assertJsonPath('data.sections.feature_groups.pdf_downloads.feature_flag', 'pdf_downloads')
        ->assertJsonPath('data.sections.feature_groups.pdf_downloads.center_settings', ['pdf_download_permission'])
        ->assertJsonPath('data.sections.feature_groups.pdf_downloads.system_overrides', ['force_disable_pdf_download'])
        ->assertJsonPath('data.sections.feature_groups.guest_browsing.center_settings', ['allow_guest_browsing'])
        ->assertJsonPath('data.sections.feature_groups.guest_browsing.system_overrides', ['force_disable_guest_browsing']);
});

it('resolves feature-gated settings as disabled when the feature flag is off', function (): void {
    $center = Center::factory()->create([
        'allow_guest_browsing' => true,
        'pdf_download_permission' => true,
    ]);

    $center->setting()->create([
        'settings' => [
            'allow_guest_browsing' => true,
            'pdf_download_permission' => true,
            'video_code_expiry_days' => 30,
            'features' => [
                'guest_browsing' => false,
                'pdf_downloads' => false,
                'codes_access' => false,
            ],
        ],
    ]);

    $response = $this->getJson("/api/v1/admin/centers/{$center->id}/settings", $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('data.settings.features.guest_browsing', false)
        ->assertJsonPath('data.resolved_settings.allow_guest_browsing', false)
        ->assertJsonPath('data.resolved_settings.pdf_download_permission', false)
        ->assertJsonPath('data.resolved_settings.video_code_expiry_days', null);
});
