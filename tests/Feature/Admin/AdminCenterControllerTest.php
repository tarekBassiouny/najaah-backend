<?php

declare(strict_types=1);

use App\Jobs\SendAdminInvitationEmailJob;
use App\Models\Center;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;

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
        'name' => 'Center One',
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
    $response->assertJsonMissingPath('data.center.api_key');
    $response->assertJsonPath('data.email_sent', true);
    $this->assertDatabaseHas('centers', ['slug' => 'center-1']);
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
    $centerOwnerRoleId = Role::query()->where('slug', 'center_owner')->value('id');
    $this->assertDatabaseHas('role_user', [
        'user_id' => (int) $owner?->id,
        'role_id' => (int) $centerOwnerRoleId,
    ]);
    $center = Center::where('slug', 'center-1')->first();
    expect($center?->onboarding_status)->toBe(Center::ONBOARDING_ACTIVE);
    Bus::assertDispatched(SendAdminInvitationEmailJob::class);
});

it('rejects branded center creation without branding metadata', function (): void {
    Bus::fake();
    Role::factory()->create(['slug' => 'center_owner']);

    $payload = [
        'slug' => 'brandless',
        'type' => 'branded',
        'name' => 'Brandless',
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
        'name' => 'Unbranded',
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

it('lists center options with minimal payload', function (): void {
    Center::factory()->create([
        'slug' => 'alpha-main',
        'type' => Center::TYPE_BRANDED,
        'name_translations' => ['en' => 'Alpha Center'],
    ]);
    Center::factory()->create([
        'slug' => 'beta-main',
        'type' => Center::TYPE_UNBRANDED,
        'name_translations' => ['en' => 'Beta Center'],
    ]);

    $response = $this->getJson('/api/v1/admin/centers/options?page=1&per_page=20&search=alpha&type=branded');

    $response->assertOk()
        ->assertJsonPath('meta.page', 1)
        ->assertJsonPath('meta.per_page', 20)
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.slug', 'alpha-main')
        ->assertJsonPath('data.0.name', 'Alpha Center')
        ->assertJsonMissingPath('data.0.setting')
        ->assertJsonMissingPath('data.0.branding_metadata');

    expect(array_keys($response->json('data.0')))->toBe(['id', 'name', 'slug']);
});

it('searches centers by name or slug', function (): void {
    Center::factory()->create([
        'slug' => 'alpha-main',
        'name_translations' => ['en' => 'Alpha Academy'],
    ]);
    Center::factory()->create([
        'slug' => 'beta-main',
        'name_translations' => ['en' => 'Beta Academy'],
    ]);

    $byName = $this->getJson('/api/v1/admin/centers?search=Alpha');
    $bySlug = $this->getJson('/api/v1/admin/centers?search=beta-main');

    $byName->assertOk()->assertJsonCount(1, 'data');
    $bySlug->assertOk()->assertJsonCount(1, 'data');
});

it('updates a center but keeps slug immutable', function (): void {
    $center = Center::factory()->create(['slug' => 'immutable']);

    $response = $this->putJson("/api/v1/admin/centers/{$center->id}", [
        'name' => 'Updated Center',
        'is_featured' => true,
    ]);

    $response->assertOk()->assertJsonPath('data.is_featured', true);
    $center->refresh();
    expect($center->slug)->toBe('immutable');
});

it('updates tier using string enums', function (): void {
    $center = Center::factory()->create([
        'tier' => Center::TIER_STANDARD,
    ]);

    $response = $this->putJson("/api/v1/admin/centers/{$center->id}", [
        'tier' => 'premium',
    ]);

    $response->assertOk();

    $center->refresh();
    expect($center->tier)->toBe(Center::TIER_PREMIUM);
});

it('updates center status via dedicated endpoint', function (): void {
    $center = Center::factory()->create([
        'status' => Center::STATUS_ACTIVE,
    ]);

    $response = $this->putJson("/api/v1/admin/centers/{$center->id}/status", [
        'status' => 0,
    ]);

    $response->assertOk()
        ->assertJsonPath('data.status', 0)
        ->assertJsonPath('data.status_label', 'Inactive');

    $center->refresh();
    expect($center->status)->toBe(Center::STATUS_INACTIVE);
});

it('rejects numeric tier on update', function (): void {
    $center = Center::factory()->create();

    $response = $this->putJson("/api/v1/admin/centers/{$center->id}", [
        'tier' => 1,
    ]);

    $response->assertStatus(422);
});

it('defaults logo path on create when missing', function (): void {
    Bus::fake();
    Role::factory()->create(['slug' => 'center_owner']);
    Storage::fake();
    Storage::put('centers/defaults/logo.png', 'logo');

    $payload = [
        'slug' => 'default-logo',
        'type' => 'unbranded',
        'name' => 'Default Logo Center',
        'admin' => [
            'name' => 'Admin User',
            'email' => 'default-logo@example.com',
        ],
    ];

    $response = $this->postJson('/api/v1/admin/centers', $payload);

    $response->assertCreated();
    $logoUrl = (string) $response->json('data.center.logo_url');
    expect($logoUrl)
        ->not->toBe('centers/defaults/logo.png')
        ->toContain('centers/defaults/logo.png');
});

it('does not expose api key in center create response', function (): void {
    Bus::fake();
    Role::factory()->create(['slug' => 'center_owner']);

    $response = $this->postJson('/api/v1/admin/centers', [
        'slug' => 'hidden-api-key',
        'type' => 'branded',
        'name' => 'Hidden API Key',
        'branding_metadata' => [
            'primary_color' => '#123456',
        ],
        'admin' => [
            'name' => 'Admin User',
            'email' => 'hidden-api-key@example.com',
        ],
    ]);

    $response->assertCreated()
        ->assertJsonMissingPath('data.center.api_key');
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

it('filters centers by status', function (): void {
    Center::factory()->create([
        'name_translations' => ['en' => 'Active Center'],
        'status' => Center::STATUS_ACTIVE,
    ]);
    Center::factory()->create([
        'name_translations' => ['en' => 'Inactive Center'],
        'status' => Center::STATUS_INACTIVE,
    ]);

    $response = $this->getJson('/api/v1/admin/centers?status=0');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Inactive Center')
        ->assertJsonPath('data.0.status', 0)
        ->assertJsonPath('data.0.status_label', 'Inactive');
});

it('supports deleted filter mode for centers list', function (): void {
    $active = Center::factory()->create();
    $deleted = Center::factory()->create();
    $deleted->delete();

    $onlyDeleted = $this->getJson('/api/v1/admin/centers?deleted=only_deleted');
    $onlyDeleted->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $deleted->id)
        ->assertJsonPath('data.0.deleted_at', fn ($value) => is_string($value) && $value !== '');

    $withDeleted = $this->getJson('/api/v1/admin/centers?deleted=with_deleted');
    $withDeleted->assertOk()
        ->assertJsonPath('meta.total', 2);

    $activeOnly = $this->getJson('/api/v1/admin/centers?deleted=active');
    $activeOnly->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $active->id)
        ->assertJsonPath('data.0.deleted_at', null);
});

it('bulk updates center statuses', function (): void {
    $toUpdate = Center::factory()->create(['status' => Center::STATUS_ACTIVE]);
    $alreadyTarget = Center::factory()->create(['status' => Center::STATUS_INACTIVE]);

    $response = $this->postJson('/api/v1/admin/centers/bulk-status', [
        'status' => 0,
        'center_ids' => [$toUpdate->id, $alreadyTarget->id, 999999],
    ]);

    $response->assertOk()
        ->assertJsonPath('data.counts.total', 3)
        ->assertJsonPath('data.counts.updated', 1)
        ->assertJsonPath('data.counts.skipped', 1)
        ->assertJsonPath('data.counts.failed', 1);

    $toUpdate->refresh();
    expect($toUpdate->status)->toBe(Center::STATUS_INACTIVE);
});

it('bulk updates center featured and tier', function (): void {
    $center = Center::factory()->create([
        'is_featured' => false,
        'tier' => Center::TIER_STANDARD,
    ]);

    $featured = $this->postJson('/api/v1/admin/centers/bulk-featured', [
        'is_featured' => true,
        'center_ids' => [$center->id],
    ]);
    $featured->assertOk()->assertJsonPath('data.counts.updated', 1);

    $tier = $this->postJson('/api/v1/admin/centers/bulk-tier', [
        'tier' => 'vip',
        'center_ids' => [$center->id],
    ]);
    $tier->assertOk()->assertJsonPath('data.counts.updated', 1);

    $center->refresh();
    expect($center->is_featured)->toBeTrue()
        ->and($center->tier)->toBe(Center::TIER_VIP);
});

it('bulk deletes and restores centers', function (): void {
    $center = Center::factory()->create();

    $delete = $this->postJson('/api/v1/admin/centers/bulk-delete', [
        'center_ids' => [$center->id],
    ]);
    $delete->assertOk()->assertJsonPath('data.counts.deleted', 1);
    $this->assertSoftDeleted('centers', ['id' => $center->id]);

    $restore = $this->postJson('/api/v1/admin/centers/bulk-restore', [
        'center_ids' => [$center->id],
    ]);
    $restore->assertOk()->assertJsonPath('data.counts.restored', 1);
    $this->assertDatabaseHas('centers', ['id' => $center->id, 'deleted_at' => null]);
});

it('allows system admin with center.manage to manage centers without super_admin role', function (): void {
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

    $response->assertNoContent();
    $this->assertSoftDeleted('centers', ['id' => $center->id]);
});

it('blocks center-scoped admin from system center management routes', function (): void {
    $this->withMiddleware();

    $center = Center::factory()->create();
    $permission = Permission::firstOrCreate(['name' => 'center.manage'], [
        'description' => 'Permission: center.manage',
    ]);
    $role = Role::factory()->create([
        'slug' => 'center_manager_scoped',
        'name' => 'Center Manager Scoped',
    ]);
    $role->permissions()->sync([$permission->id]);

    $centerScopedAdmin = User::factory()->create([
        'password' => 'secret123',
        'is_student' => false,
        'center_id' => $center->id,
    ]);
    $centerScopedAdmin->roles()->syncWithoutDetaching([$role->id]);

    $token = Auth::guard('admin')->attempt([
        'email' => $centerScopedAdmin->email,
        'password' => 'secret123',
        'is_student' => false,
    ]);

    $headers = $this->adminHeaders(['Authorization' => 'Bearer '.$token]);

    $response = $this->deleteJson("/api/v1/admin/centers/{$center->id}", [], $headers);
    $response->assertStatus(403)
        ->assertJsonPath('error.code', 'PERMISSION_DENIED');
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
        'name' => 'Center',
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
        'name' => 'Numeric Enums',
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
        'name' => 'Legacy Fields',
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
