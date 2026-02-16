<?php

declare(strict_types=1);

use App\Enums\AdminNotificationType;
use App\Models\AdminNotification;
use App\Models\Center;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;

uses(RefreshDatabase::class)->group('admin-notifications');

function adminNotificationHeaders(?User $admin = null): array
{
    if ($admin === null) {
        $role = Role::query()->where('slug', 'super_admin')->firstOrFail();
        $admin = User::factory()->create([
            'password' => 'secret123',
            'is_student' => false,
            'center_id' => null,
        ]);
        $admin->roles()->syncWithoutDetaching([$role->id]);
    }

    $token = (string) Auth::guard('admin')->attempt([
        'email' => $admin->email,
        'password' => 'secret123',
        'is_student' => false,
    ]);

    $systemKey = (string) Config::get('services.system_api_key', '');
    if ($systemKey === '') {
        $systemKey = 'system-test-key';
        Config::set('services.system_api_key', $systemKey);
    }

    return [
        'Accept' => 'application/json',
        'Authorization' => 'Bearer ' . $token,
        'X-Api-Key' => $systemKey,
    ];
}

it('lists notifications for system super admin', function (): void {
    $headers = adminNotificationHeaders();

    AdminNotification::factory()->count(3)->create();
    AdminNotification::factory()->read()->count(2)->create();

    $response = $this->getJson('/api/v1/admin/notifications', $headers);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(5, 'data');
});

it('filters unread notifications only', function (): void {
    $headers = adminNotificationHeaders();

    AdminNotification::factory()->unread()->count(3)->create();
    AdminNotification::factory()->read()->count(2)->create();

    $response = $this->getJson('/api/v1/admin/notifications?unread_only=1', $headers);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(3, 'data');
});

it('filters notifications by type', function (): void {
    $headers = adminNotificationHeaders();

    AdminNotification::factory()->deviceChangeRequest()->count(2)->create();
    AdminNotification::factory()->extraViewRequest()->count(3)->create();

    $typeValue = AdminNotificationType::DEVICE_CHANGE_REQUEST->value;
    $response = $this->getJson("/api/v1/admin/notifications?type={$typeValue}", $headers);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(2, 'data');
});

it('filters notifications by since timestamp for polling', function (): void {
    $headers = adminNotificationHeaders();

    $oldNotification = AdminNotification::factory()->create([
        'created_at' => now()->subHours(2),
    ]);
    $newNotification = AdminNotification::factory()->create([
        'created_at' => now()->subMinutes(5),
    ]);

    $sinceTimestamp = now()->subHour()->timestamp;
    $response = $this->getJson("/api/v1/admin/notifications?since={$sinceTimestamp}", $headers);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', (int) $newNotification->id);
});

it('returns unread count for polling', function (): void {
    $headers = adminNotificationHeaders();

    AdminNotification::factory()->unread()->count(5)->create();
    AdminNotification::factory()->read()->count(3)->create();

    $response = $this->getJson('/api/v1/admin/notifications/count', $headers);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.unread_count', 5);
});

it('marks a notification as read', function (): void {
    $headers = adminNotificationHeaders();

    $notification = AdminNotification::factory()->unread()->create();

    $response = $this->putJson("/api/v1/admin/notifications/{$notification->id}/read", [], $headers);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.is_read', true);

    $this->assertDatabaseMissing('admin_notifications', [
        'id' => (int) $notification->id,
        'read_at' => null,
    ]);
});

it('marks all notifications as read', function (): void {
    $headers = adminNotificationHeaders();

    AdminNotification::factory()->unread()->count(5)->create();

    $response = $this->postJson('/api/v1/admin/notifications/read-all', [], $headers);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.marked_count', 5);

    expect(AdminNotification::query()->unread()->count())->toBe(0);
});

it('deletes a notification', function (): void {
    $headers = adminNotificationHeaders();

    $notification = AdminNotification::factory()->create();

    $response = $this->deleteJson("/api/v1/admin/notifications/{$notification->id}", [], $headers);

    $response->assertNoContent();

    $this->assertSoftDeleted('admin_notifications', [
        'id' => (int) $notification->id,
    ]);
});

it('shows center-scoped notifications to center admin', function (): void {
    $center = Center::factory()->create();
    $role = Role::query()->where('slug', 'super_admin')->firstOrFail();

    $centerAdmin = User::factory()->create([
        'password' => 'secret123',
        'is_student' => false,
        'center_id' => $center->id,
    ]);
    $centerAdmin->roles()->syncWithoutDetaching([$role->id]);
    $centerAdmin->centers()->syncWithoutDetaching([
        (int) $center->id => ['type' => 'admin'],
    ]);

    $headers = adminNotificationHeaders($centerAdmin);

    $centerNotification = AdminNotification::factory()->forCenter($center)->create();
    $broadcastNotification = AdminNotification::factory()->create();
    $otherCenterNotification = AdminNotification::factory()->forCenter(Center::factory()->create())->create();

    $response = $this->getJson('/api/v1/admin/notifications', $headers);

    $response->assertOk()
        ->assertJsonPath('success', true);

    $ids = collect((array) $response->json('data'))
        ->pluck('id')
        ->map(static fn ($id): int => (int) $id)
        ->all();

    expect($ids)
        ->toContain((int) $centerNotification->id)
        ->toContain((int) $broadcastNotification->id)
        ->not->toContain((int) $otherCenterNotification->id);
});

it('shows user-targeted notifications to specific admin', function (): void {
    $role = Role::query()->where('slug', 'super_admin')->firstOrFail();

    $targetAdmin = User::factory()->create([
        'password' => 'secret123',
        'is_student' => false,
        'center_id' => null,
    ]);
    $targetAdmin->roles()->syncWithoutDetaching([$role->id]);

    $otherAdmin = User::factory()->create([
        'password' => 'secret123',
        'is_student' => false,
        'center_id' => null,
    ]);
    $otherAdmin->roles()->syncWithoutDetaching([$role->id]);

    $targetedNotification = AdminNotification::factory()->forUser($targetAdmin)->create();
    $otherNotification = AdminNotification::factory()->forUser($otherAdmin)->create();

    $headers = adminNotificationHeaders($targetAdmin);
    $response = $this->getJson('/api/v1/admin/notifications', $headers);

    $response->assertOk();

    $ids = collect((array) $response->json('data'))
        ->pluck('id')
        ->map(static fn ($id): int => (int) $id)
        ->all();

    expect($ids)
        ->toContain((int) $targetedNotification->id)
        ->not->toContain((int) $otherNotification->id);
});

it('returns notification with correct structure', function (): void {
    $headers = adminNotificationHeaders();

    AdminNotification::factory()->deviceChangeRequest()->create([
        'title' => 'Test Device Change',
        'body' => 'Test body content',
        'data' => ['entity_type' => 'device_change_request', 'entity_id' => 123],
    ]);

    $response = $this->getJson('/api/v1/admin/notifications', $headers);

    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'type_label',
                    'type_label_translations',
                    'type_icon',
                    'title',
                    'body',
                    'data',
                    'is_read',
                    'read_at',
                    'created_at',
                ],
            ],
            'meta',
        ])
        ->assertJsonPath('data.0.type', AdminNotificationType::DEVICE_CHANGE_REQUEST->value)
        ->assertJsonPath('data.0.type_label', 'Device Change Request')
        ->assertJsonPath('data.0.type_icon', 'smartphone');
});
