<?php

declare(strict_types=1);

use App\Models\Center;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('centers', 'admin');

beforeEach(function (): void {
    $this->withoutMiddleware();
    $this->asAdmin();
});

it('creates a center', function (): void {
    $payload = [
        'slug' => 'center-1',
        'type' => 1,
        'name_translations' => ['en' => 'Center One'],
        'description_translations' => ['en' => 'Desc'],
        'logo_url' => 'https://example.com/logo.png',
        'primary_color' => '#123456',
        'default_view_limit' => 3,
        'allow_extra_view_requests' => true,
        'pdf_download_permission' => false,
        'device_limit' => 2,
        'settings' => ['view_limit' => 3],
    ];

    $response = $this->postJson('/admin/centers', $payload);

    $response->assertCreated()->assertJsonPath('data.slug', 'center-1');
    $this->assertDatabaseHas('centers', ['slug' => 'center-1']);
    $this->assertDatabaseHas('center_settings', ['center_id' => $response->json('data.id')]);
});

it('lists centers with pagination', function (): void {
    Center::factory()->count(3)->create();

    $response = $this->getJson('/admin/centers?per_page=2');

    $response->assertOk()->assertJsonPath('meta.per_page', 2);
});

it('updates a center but keeps slug immutable', function (): void {
    $center = Center::factory()->create(['slug' => 'immutable']);

    $response = $this->putJson("/admin/centers/{$center->id}", [
        'slug' => 'new-slug',
        'primary_color' => '#654321',
    ]);

    $response->assertOk()->assertJsonPath('data.primary_color', '#654321');
    $center->refresh();
    expect($center->slug)->toBe('immutable');
});

it('soft deletes and restores a center', function (): void {
    $center = Center::factory()->create();

    $delete = $this->deleteJson("/admin/centers/{$center->id}");
    $delete->assertNoContent();
    $this->assertSoftDeleted('centers', ['id' => $center->id]);

    $restore = $this->postJson("/admin/centers/{$center->id}/restore");
    $restore->assertOk()->assertJsonPath('success', true);
    $this->assertDatabaseHas('centers', ['id' => $center->id, 'deleted_at' => null]);
});

it('rejects duplicate slug on create', function (): void {
    Center::factory()->create(['slug' => 'dupe']);

    $response = $this->postJson('/admin/centers', [
        'slug' => 'dupe',
        'type' => 1,
        'name_translations' => ['en' => 'Center'],
    ]);

    $response->assertStatus(422);
});
