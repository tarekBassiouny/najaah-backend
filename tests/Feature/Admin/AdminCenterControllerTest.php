<?php

declare(strict_types=1);

use App\Jobs\CreateCenterBunnyLibrary;
use App\Jobs\SendCenterOnboardingEmail;
use App\Models\Center;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;

uses(RefreshDatabase::class)->group('centers', 'admin');

beforeEach(function (): void {
    $this->withoutMiddleware();
    $this->asAdmin();
});

it('creates a center', function (): void {
    Bus::fake();
    Role::factory()->create(['slug' => 'center_owner']);

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
        'owner' => [
            'name' => 'Owner User',
            'email' => 'owner@example.com',
            'phone' => '1234567890',
        ],
    ];

    $response = $this->postJson('/api/v1/admin/centers', $payload);

    $response->assertCreated()->assertJsonPath('data.center.slug', 'center-1');
    $response->assertJsonPath('data.email_sent', true);
    $this->assertDatabaseHas('centers', ['slug' => 'center-1']);
    $this->assertDatabaseHas('center_settings', ['center_id' => $response->json('data.center.id')]);
    $this->assertDatabaseHas('user_centers', [
        'center_id' => $response->json('data.center.id'),
        'type' => 'owner',
    ]);
    $this->assertDatabaseHas('users', [
        'email' => 'owner@example.com',
        'force_password_reset' => true,
    ]);
    $owner = User::where('email', 'owner@example.com')->first();
    expect($owner)->not->toBeNull();
    Bus::assertDispatched(SendCenterOnboardingEmail::class);
    Bus::assertDispatched(CreateCenterBunnyLibrary::class);
});

it('creates a center with an existing owner', function (): void {
    Bus::fake();
    Role::factory()->create(['slug' => 'center_owner']);

    $owner = User::factory()->create([
        'is_student' => false,
        'email' => 'existing-owner@example.com',
        'center_id' => null,
    ]);

    $payload = [
        'name' => 'Existing Owner Center',
        'owner_user_id' => $owner->id,
    ];

    $response = $this->postJson('/api/v1/admin/centers', $payload);

    $response->assertCreated()->assertJsonPath('data.owner.id', $owner->id);
    $response->assertJsonPath('data.email_sent', true);
    $this->assertDatabaseHas('user_centers', [
        'user_id' => $owner->id,
        'type' => 'owner',
    ]);
    Bus::assertDispatched(SendCenterOnboardingEmail::class);
    Bus::assertDispatched(CreateCenterBunnyLibrary::class);
});

it('lists centers with pagination', function (): void {
    Center::factory()->count(3)->create();

    $response = $this->getJson('/api/v1/admin/centers?per_page=2');

    $response->assertOk()->assertJsonPath('meta.per_page', 2);
});

it('searches centers by name', function (): void {
    Center::factory()->create([
        'name_translations' => ['en' => 'Alpha Academy'],
    ]);
    Center::factory()->create([
        'name_translations' => ['en' => 'Beta Academy'],
    ]);

    $response = $this->getJson('/api/v1/admin/centers?search=Alpha');

    $response->assertOk()->assertJsonCount(1, 'data');
});

it('updates a center but keeps slug immutable', function (): void {
    $center = Center::factory()->create(['slug' => 'immutable']);

    $response = $this->putJson("/api/v1/admin/centers/{$center->id}", [
        'slug' => 'new-slug',
        'primary_color' => '#654321',
    ]);

    $response->assertOk()->assertJsonPath('data.primary_color', '#654321');
    $center->refresh();
    expect($center->slug)->toBe('immutable');
});

it('soft deletes and restores a center', function (): void {
    $center = Center::factory()->create();

    $delete = $this->deleteJson("/api/v1/admin/centers/{$center->id}");
    $delete->assertNoContent();
    $this->assertSoftDeleted('centers', ['id' => $center->id]);

    $restore = $this->postJson("/api/v1/admin/centers/{$center->id}/restore");
    $restore->assertOk()->assertJsonPath('success', true);
    $this->assertDatabaseHas('centers', ['id' => $center->id, 'deleted_at' => null]);
});

it('rejects duplicate slug on create', function (): void {
    Center::factory()->create(['slug' => 'dupe']);
    Role::factory()->create(['slug' => 'center_owner']);

    $response = $this->postJson('/api/v1/admin/centers', [
        'slug' => 'dupe',
        'type' => 1,
        'name_translations' => ['en' => 'Center'],
        'owner' => [
            'name' => 'Owner User',
            'email' => 'dupe-owner@example.com',
        ],
    ]);

    $response->assertStatus(422);
});
