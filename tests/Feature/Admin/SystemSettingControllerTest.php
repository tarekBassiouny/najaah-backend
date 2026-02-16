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
        'key' => 'student.default_country_code',
        'value' => ['code' => '+20'],
        'is_public' => true,
    ]);
    SystemSetting::factory()->create([
        'key' => 'internal.jwt_ttl',
        'value' => ['minutes' => 60],
        'is_public' => false,
    ]);

    $response = $this->getJson('/api/v1/admin/settings?page=1&per_page=20&search=student&is_public=1', $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.key', 'student.default_country_code')
        ->assertJsonPath('meta.page', 1)
        ->assertJsonPath('meta.per_page', 20)
        ->assertJsonPath('meta.total', 1);
});

it('creates a system setting', function (): void {
    $response = $this->postJson('/api/v1/admin/settings', [
        'key' => 'student.default_country_code',
        'value' => ['code' => '+20'],
        'is_public' => true,
    ], $this->adminHeaders());

    $response->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.key', 'student.default_country_code')
        ->assertJsonPath('data.value.code', '+20')
        ->assertJsonPath('data.is_public', true);

    $this->assertDatabaseHas('system_settings', [
        'key' => 'student.default_country_code',
        'is_public' => 1,
    ]);
});

it('shows a system setting', function (): void {
    $setting = SystemSetting::factory()->create([
        'key' => 'student.default_country_code',
        'value' => ['code' => '+20'],
        'is_public' => true,
    ]);

    $response = $this->getJson("/api/v1/admin/settings/{$setting->id}", $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $setting->id)
        ->assertJsonPath('data.key', 'student.default_country_code')
        ->assertJsonPath('data.value.code', '+20');
});

it('updates a system setting', function (): void {
    $setting = SystemSetting::factory()->create([
        'key' => 'student.default_country_code',
        'value' => ['code' => '+20'],
        'is_public' => true,
    ]);

    $response = $this->putJson("/api/v1/admin/settings/{$setting->id}", [
        'value' => ['code' => '+966'],
        'is_public' => false,
    ], $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $setting->id)
        ->assertJsonPath('data.value.code', '+966')
        ->assertJsonPath('data.is_public', false);

    $this->assertDatabaseHas('system_settings', [
        'id' => $setting->id,
        'is_public' => 0,
    ]);
});

it('deletes a system setting', function (): void {
    $setting = SystemSetting::factory()->create();

    $response = $this->deleteJson("/api/v1/admin/settings/{$setting->id}", [], $this->adminHeaders());

    $response->assertStatus(204);
    $this->assertSoftDeleted('system_settings', ['id' => $setting->id]);
});

it('validates required fields on create', function (): void {
    $response = $this->postJson('/api/v1/admin/settings', [
        'value' => ['code' => '+20'],
    ], $this->adminHeaders());

    $response->assertStatus(422)
        ->assertJsonPath('error.code', 'VALIDATION_ERROR')
        ->assertJsonPath('error.details.key.0', 'The key field is required.');
});

it('requires authentication', function (): void {
    auth('admin')->logout();

    $response = $this->getJson('/api/v1/admin/settings');

    $response->assertStatus(401);
});
