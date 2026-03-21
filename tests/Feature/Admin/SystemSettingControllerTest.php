<?php

declare(strict_types=1);

use App\Models\SystemSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('admin', 'settings');

beforeEach(function (): void {
    $this->asAdmin();
});

it('lists system settings with filters', function (): void {
    SystemSetting::factory()->create([
        'key' => 'support_email',
        'value' => ['email' => 'ops@example.com'],
        'is_public' => true,
    ]);
    SystemSetting::factory()->create([
        'key' => 'attendance_required',
        'value' => ['enabled' => true],
        'is_public' => false,
    ]);

    $response = $this->getJson('/api/v1/admin/settings?page=1&per_page=20&search=support&is_public=1', $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.key', 'support_email')
        ->assertJsonPath('meta.page', 1)
        ->assertJsonPath('meta.per_page', 20)
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('meta.catalog.support_email.scope', 'system')
        ->assertJsonPath('meta.catalog_groups.general.0', 'site_name')
        ->assertJsonPath('meta.defaults.support_email', 'ops@example.com');
});

it('creates a system setting', function (): void {
    $response = $this->postJson('/api/v1/admin/settings', [
        'key' => 'support_email',
        'value' => ['email' => 'support@example.com'],
        'is_public' => true,
    ], $this->adminHeaders());

    $response->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.key', 'support_email')
        ->assertJsonPath('data.value.email', 'support@example.com')
        ->assertJsonPath('data.is_public', true);

    $this->assertDatabaseHas('system_settings', [
        'key' => 'support_email',
        'is_public' => 1,
    ]);
});

it('shows a system setting', function (): void {
    $setting = SystemSetting::factory()->create([
        'key' => 'max_device_limit',
        'value' => ['value' => 3],
        'is_public' => true,
    ]);

    $response = $this->getJson("/api/v1/admin/settings/{$setting->id}", $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $setting->id)
        ->assertJsonPath('data.key', 'max_device_limit')
        ->assertJsonPath('data.value.value', 3);
});

it('updates a system setting', function (): void {
    $setting = SystemSetting::factory()->create([
        'key' => 'max_device_limit',
        'value' => ['value' => 3],
        'is_public' => true,
    ]);

    $response = $this->putJson("/api/v1/admin/settings/{$setting->id}", [
        'value' => ['value' => 5],
        'is_public' => false,
    ], $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $setting->id)
        ->assertJsonPath('data.value.value', 5)
        ->assertJsonPath('data.is_public', false);

    $this->assertDatabaseHas('system_settings', [
        'id' => $setting->id,
        'is_public' => 0,
    ]);
});

it('deletes a system setting', function (): void {
    $setting = SystemSetting::factory()->create();

    $response = $this->deleteJson("/api/v1/admin/settings/{$setting->id}", [], $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data', null);
    $this->assertSoftDeleted('system_settings', ['id' => $setting->id]);
});

it('validates required fields on create', function (): void {
    $response = $this->postJson('/api/v1/admin/settings', [
        'value' => ['email' => 'support@example.com'],
    ], $this->adminHeaders());

    $response->assertStatus(422)
        ->assertJsonPath('error.code', 'VALIDATION_ERROR')
        ->assertJsonPath('error.details.key.0', 'The key field is required.');
});

it('rejects unknown system setting keys', function (): void {
    $response = $this->postJson('/api/v1/admin/settings', [
        'key' => 'student.default_country_code',
        'value' => ['code' => '+20'],
        'is_public' => true,
    ], $this->adminHeaders());

    $response->assertStatus(422)
        ->assertJsonPath('error.code', 'VALIDATION_ERROR');
});

it('requires authentication', function (): void {
    auth('admin')->logout();

    $response = $this->getJson('/api/v1/admin/settings');

    $response->assertStatus(401);
});
