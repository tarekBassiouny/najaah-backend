<?php

declare(strict_types=1);

use App\Jobs\CreateBunnyLibraryJob;
use App\Jobs\ProcessCenterLogoJob;
use App\Jobs\SendAdminInvitationEmailJob;
use App\Models\Center;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
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
        'type' => 'branded',
        'tier' => 'premium',
        'is_featured' => true,
        'name_translations' => ['en' => 'Center One'],
        'branding_metadata' => [
            'primary_color' => '#123456',
        ],
        'admin' => [
            'name' => 'Owner User',
            'email' => 'owner@example.com',
        ],
    ];

    $response = $this->postJson('/api/v1/admin/centers', $payload);

    $response->assertCreated()->assertJsonPath('data.center.slug', 'center-1');
    $response->assertJsonPath('data.center.type', 'branded');
    $response->assertJsonPath('data.center.tier', 'premium');
    $response->assertJsonPath('data.center.api_key', $response->json('data.center.api_key'));
    $response->assertJsonPath('data.email_sent', false);
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
    $center = Center::where('slug', 'center-1')->first();
    expect($center?->onboarding_status)->toBe(Center::ONBOARDING_ACTIVE);
    Bus::assertDispatched(CreateBunnyLibraryJob::class);
    Bus::assertDispatched(SendAdminInvitationEmailJob::class);
    Bus::assertDispatched(ProcessCenterLogoJob::class);
});

it('rejects branded center creation without branding metadata', function (): void {
    Bus::fake();
    Role::factory()->create(['slug' => 'center_owner']);

    $payload = [
        'slug' => 'brandless',
        'type' => 'branded',
        'name_translations' => ['en' => 'Brandless'],
        'admin' => [
            'name' => 'Admin User',
            'email' => 'brandless-owner@example.com',
        ],
    ];

    $response = $this->postJson('/api/v1/admin/centers', $payload);

    $response->assertStatus(422);
});

it('allows unbranded center creation without branding metadata', function (): void {
    Bus::fake();
    Role::factory()->create(['slug' => 'center_owner']);

    $payload = [
        'slug' => 'unbranded',
        'type' => 'unbranded',
        'name_translations' => ['en' => 'Unbranded'],
        'admin' => [
            'name' => 'Admin User',
            'email' => 'unbranded-owner@example.com',
        ],
    ];

    $response = $this->postJson('/api/v1/admin/centers', $payload);

    $response->assertCreated();
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
        'name_translations' => ['en' => 'Updated Center'],
        'is_featured' => true,
    ]);

    $response->assertOk()->assertJsonPath('data.is_featured', true);
    $center->refresh();
    expect($center->slug)->toBe('immutable');
});

it('defaults logo path on create when missing', function (): void {
    Bus::fake();
    Role::factory()->create(['slug' => 'center_owner']);

    $payload = [
        'slug' => 'default-logo',
        'type' => 'unbranded',
        'name_translations' => ['en' => 'Default Logo Center'],
        'admin' => [
            'name' => 'Admin User',
            'email' => 'default-logo@example.com',
        ],
    ];

    $response = $this->postJson('/api/v1/admin/centers', $payload);

    $response->assertCreated()->assertJsonPath('data.center.logo_url', 'centers/defaults/logo.png');
});

it('does not expose api key in center show', function (): void {
    $center = Center::factory()->create([
        'api_key' => 'center-secret-key',
    ]);

    $response = $this->getJson("/api/v1/admin/centers/{$center->id}");

    $response->assertOk()->assertJsonMissing(['api_key' => 'center-secret-key']);
});

it('filters centers by featured and onboarding status', function (): void {
    Center::factory()->create([
        'name_translations' => ['en' => 'Alpha Center'],
        'is_featured' => true,
        'onboarding_status' => Center::ONBOARDING_ACTIVE,
    ]);
    Center::factory()->create([
        'name_translations' => ['en' => 'Beta Center'],
        'is_featured' => false,
        'onboarding_status' => Center::ONBOARDING_FAILED,
    ]);

    $response = $this->getJson('/api/v1/admin/centers?search=Alpha&is_featured=1&onboarding_status=ACTIVE');

    $response->assertOk()->assertJsonCount(1, 'data');
});

it('filters centers by tier and date range', function (): void {
    Center::factory()->create([
        'tier' => Center::TIER_STANDARD,
        'created_at' => now()->subDays(10),
    ]);

    Center::factory()->create([
        'tier' => Center::TIER_PREMIUM,
        'created_at' => now()->subDays(1),
    ]);

    $query = http_build_query([
        'tier' => Center::TIER_PREMIUM,
        'created_from' => now()->subDays(2)->toDateString(),
        'created_to' => now()->toDateString(),
    ]);

    $response = $this->getJson('/api/v1/admin/centers?'.$query);

    $response->assertOk()->assertJsonCount(1, 'data');
});

it('blocks delete for non super admins', function (): void {
    $this->withMiddleware();

    $center = Center::factory()->create();

    $permission = Permission::firstOrCreate(['name' => 'center.manage'], [
        'description' => 'Permission: center.manage',
    ]);

    $role = Role::factory()->create([
        'slug' => 'center_manager',
        'name' => 'Center Manager',
    ]);
    $role->permissions()->sync([$permission->id]);

    $admin = User::factory()->create([
        'password' => 'secret123',
        'is_student' => false,
        'center_id' => null,
    ]);
    $admin->roles()->syncWithoutDetaching([$role->id]);

    $token = Auth::guard('admin')->attempt([
        'email' => $admin->email,
        'password' => 'secret123',
        'is_student' => false,
    ]);

    $headers = $this->adminHeaders(['Authorization' => 'Bearer '.$token]);

    $response = $this->deleteJson("/api/v1/admin/centers/{$center->id}", [], $headers);

    $response->assertStatus(403);
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
        'type' => 'branded',
        'name_translations' => ['en' => 'Center'],
        'branding_metadata' => [
            'primary_color' => '#123456',
        ],
        'admin' => [
            'name' => 'Admin User',
            'email' => 'dupe-owner@example.com',
        ],
    ]);

    $response->assertStatus(422);
});

it('rejects numeric enums on create', function (): void {
    Role::factory()->create(['slug' => 'center_owner']);

    $response = $this->postJson('/api/v1/admin/centers', [
        'slug' => 'numeric-enums',
        'type' => 1,
        'tier' => 2,
        'name_translations' => ['en' => 'Numeric Enums'],
        'branding_metadata' => [
            'primary_color' => '#123456',
        ],
        'admin' => [
            'name' => 'Admin User',
            'email' => 'numeric-enums@example.com',
        ],
    ]);

    $response->assertStatus(422);
});

it('rejects legacy fields on create', function (): void {
    Role::factory()->create(['slug' => 'center_owner']);

    $response = $this->postJson('/api/v1/admin/centers', [
        'slug' => 'legacy-fields',
        'type' => 'branded',
        'name_translations' => ['en' => 'Legacy Fields'],
        'branding_metadata' => [
            'primary_color' => '#123456',
        ],
        'admin' => [
            'name' => 'Admin User',
            'email' => 'legacy-fields@example.com',
        ],
        'logo_url' => 'https://example.com/logo.png',
        'settings' => ['pdf_download_permission' => true],
    ]);

    $response->assertStatus(422);
});

it('rejects invalid name_translations structure', function (): void {
    Role::factory()->create(['slug' => 'center_owner']);

    $response = $this->postJson('/api/v1/admin/centers', [
        'slug' => 'bad-translations',
        'type' => 'unbranded',
        'name_translations' => 'Center Name',
        'admin' => [
            'name' => 'Admin User',
            'email' => 'bad-translations@example.com',
        ],
    ]);

    $response->assertStatus(422);
});
