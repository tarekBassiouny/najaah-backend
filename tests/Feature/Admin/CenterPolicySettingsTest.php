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
