<?php

declare(strict_types=1);

use App\Models\AuditLog;
use App\Models\Center;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('audit-logs', 'admin');

beforeEach(function (): void {
    $this->withoutMiddleware();
    $this->asAdmin();
});

it('lists audit logs with pagination', function (): void {
    AuditLog::factory()->count(3)->create();

    $response = $this->getJson('/api/v1/admin/audit-logs?per_page=2');

    $response->assertOk()->assertJsonPath('meta.per_page', 2);
});

it('filters by entity and action', function (): void {
    AuditLog::factory()->create([
        'entity_type' => User::class,
        'entity_id' => 1,
        'action' => 'created',
    ]);

    AuditLog::factory()->create([
        'entity_type' => 'Other',
        'entity_id' => 2,
        'action' => 'updated',
    ]);

    $response = $this->getJson('/api/v1/admin/audit-logs?entity_type='.urlencode(User::class).'&entity_id=1&action=created');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.entity_type', User::class)
        ->assertJsonPath('data.0.entity_id', 1)
        ->assertJsonPath('data.0.action', 'created');
});

it('filters by user', function (): void {
    /** @var User $actor */
    $actor = User::factory()->create(['is_student' => false]);

    AuditLog::factory()->create(['user_id' => $actor->id]);
    AuditLog::factory()->create(['user_id' => null]);

    $response = $this->getJson("/api/v1/admin/audit-logs?user_id={$actor->id}");

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.user_id', $actor->id);
});

it('filters by date range', function (): void {
    AuditLog::factory()->create(['created_at' => now()->subDays(2)]);
    AuditLog::factory()->create(['created_at' => now()]);

    $response = $this->getJson('/api/v1/admin/audit-logs?date_from='.now()->toDateString());

    $response->assertOk()
        ->assertJsonCount(1, 'data');
});

it('filters by center for super admin', function (): void {
    $super = $this->asAdmin();
    $centerA = Center::factory()->create();
    $centerB = Center::factory()->create();

    $userA = User::factory()->create([
        'center_id' => $centerA->id,
        'phone' => '1000000001',
    ]);
    $userB = User::factory()->create([
        'center_id' => $centerB->id,
        'phone' => '1000000002',
    ]);

    AuditLog::factory()->create(['user_id' => $userA->id]);
    AuditLog::factory()->create(['user_id' => $userB->id]);

    $response = $this->actingAs($super, 'admin')
        ->getJson('/api/v1/admin/audit-logs?center_id='.$centerA->id);

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.user_id', $userA->id);
});

it('scopes audit logs to admin center', function (): void {
    $centerA = Center::factory()->create();
    $centerB = Center::factory()->create();

    $admin = User::factory()->create([
        'is_student' => false,
        'center_id' => $centerA->id,
    ]);

    $userA = User::factory()->create([
        'center_id' => $centerA->id,
        'phone' => '1000000003',
    ]);
    $userB = User::factory()->create([
        'center_id' => $centerB->id,
        'phone' => '1000000004',
    ]);

    AuditLog::factory()->create(['user_id' => $userA->id]);
    AuditLog::factory()->create(['user_id' => $userB->id]);

    $response = $this->actingAs($admin, 'admin')
        ->getJson('/api/v1/admin/audit-logs?center_id='.$centerB->id);

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.user_id', $userA->id);
});

it('requires authentication', function (): void {
    AuditLog::factory()->create();
    auth('admin')->logout();

    $response = $this->getJson('/api/v1/admin/audit-logs');

    $response->assertStatus(401);
});
