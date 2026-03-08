<?php

declare(strict_types=1);

use App\Models\Center;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('timezone', 'centers', 'admin');

it('includes timezone in center resource', function (): void {
    $center = Center::factory()->create([
        'timezone' => 'Africa/Cairo',
    ]);

    $this->asCenterAdmin($center);
    $response = $this->getJson("/api/v1/admin/centers/{$center->id}", $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.timezone', 'Africa/Cairo')
        ->assertJsonPath('data.effective_timezone', 'Africa/Cairo');
});

it('shows effective timezone when center has no explicit timezone', function (): void {
    $center = Center::factory()->create([
        'timezone' => 'UTC',
    ]);

    $this->asCenterAdmin($center);
    $response = $this->getJson("/api/v1/admin/centers/{$center->id}", $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('data.timezone', 'UTC')
        ->assertJsonPath('data.effective_timezone', 'UTC');
});

it('allows updating center timezone', function (): void {
    $center = Center::factory()->create([
        'timezone' => 'UTC',
    ]);

    $this->asCenterAdmin($center);
    $response = $this->patchJson("/api/v1/admin/centers/{$center->id}", [
        'timezone' => 'Asia/Riyadh',
    ], $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.timezone', 'Asia/Riyadh');

    $center->refresh();
    expect($center->timezone)->toBe('Asia/Riyadh');
});

it('validates timezone format', function (): void {
    $center = Center::factory()->create();

    $this->asCenterAdmin($center);
    $response = $this->patchJson("/api/v1/admin/centers/{$center->id}", [
        'timezone' => 'Invalid/Timezone',
    ], $this->adminHeaders());

    $response->assertStatus(422)
        ->assertJsonPath('success', false);
});

it('allows null timezone to use system default', function (): void {
    $center = Center::factory()->create([
        'timezone' => 'Asia/Dubai',
    ]);

    $this->asCenterAdmin($center);
    $response = $this->patchJson("/api/v1/admin/centers/{$center->id}", [
        'timezone' => null,
    ], $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('success', true);
});
