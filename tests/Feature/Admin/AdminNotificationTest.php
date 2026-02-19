<?php

declare(strict_types=1);

use App\Enums\AdminNotificationType;
use App\Models\AdminNotification;
use App\Models\AdminNotificationUserState;
use App\Models\Center;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Permission;
use App\Models\Pivots\CourseVideo;
use App\Models\Role;
use App\Models\User;
use App\Models\Video;
use App\Services\Playback\ExtraViewRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;

uses(RefreshDatabase::class)->group('admin-notifications');

beforeEach(function (): void {
    Role::firstOrCreate(
        ['slug' => 'super_admin'],
        [
            'name' => 'Super Admin',
            'name_translations' => ['en' => 'Super Admin'],
            'description_translations' => ['en' => 'Full system administrator'],
        ]
    );
});

function adminNotificationHeaders(?User $admin = null): array
{
    if ($admin === null) {
        $role = Role::query()->firstOrCreate(
            ['slug' => 'super_admin'],
            [
                'name' => 'Super Admin',
                'name_translations' => ['en' => 'Super Admin'],
            ]
        );
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
        'headers' => [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer '.$token,
            'X-Api-Key' => $systemKey,
        ],
        'admin' => $admin,
    ];
}

it('lists notifications for system super admin', function (): void {
    ['headers' => $headers] = adminNotificationHeaders();

    AdminNotification::factory()->count(5)->create();

    $response = $this->getJson('/api/v1/admin/notifications', $headers);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(5, 'data');
});

it('filters unread notifications only', function (): void {
    ['headers' => $headers, 'admin' => $admin] = adminNotificationHeaders();

    AdminNotification::factory()->count(3)->create();
    $readNotifications = AdminNotification::factory()->count(2)->create();
    foreach ($readNotifications as $notification) {
        AdminNotificationUserState::create([
            'admin_notification_id' => (int) $notification->id,
            'user_id' => (int) $admin->id,
            'read_at' => now(),
        ]);
    }

    $response = $this->getJson('/api/v1/admin/notifications?unread_only=1', $headers);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(3, 'data');
});

it('filters notifications by type', function (): void {
    ['headers' => $headers] = adminNotificationHeaders();

    AdminNotification::factory()->deviceChangeRequest()->count(2)->create();
    AdminNotification::factory()->extraViewRequest()->count(3)->create();

    $typeValue = AdminNotificationType::DEVICE_CHANGE_REQUEST->value;
    $response = $this->getJson("/api/v1/admin/notifications?type={$typeValue}", $headers);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(2, 'data');
});

it('filters notifications by since timestamp for polling', function (): void {
    ['headers' => $headers] = adminNotificationHeaders();

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
    ['headers' => $headers, 'admin' => $admin] = adminNotificationHeaders();

    AdminNotification::factory()->count(5)->create();
    $readNotifications = AdminNotification::factory()->count(3)->create();
    foreach ($readNotifications as $notification) {
        AdminNotificationUserState::create([
            'admin_notification_id' => (int) $notification->id,
            'user_id' => (int) $admin->id,
            'read_at' => now(),
        ]);
    }

    $response = $this->getJson('/api/v1/admin/notifications/count', $headers);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.unread_count', 5);
});

it('marks a notification as read', function (): void {
    ['headers' => $headers, 'admin' => $admin] = adminNotificationHeaders();

    $notification = AdminNotification::factory()->create();

    $response = $this->putJson("/api/v1/admin/notifications/{$notification->id}/read", [], $headers);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.is_read', true);

    $state = AdminNotificationUserState::query()
        ->where('admin_notification_id', (int) $notification->id)
        ->where('user_id', (int) $admin->id)
        ->firstOrFail();

    expect($state->read_at)->not->toBeNull();
});

it('marks all notifications as read', function (): void {
    ['headers' => $headers, 'admin' => $admin] = adminNotificationHeaders();

    AdminNotification::factory()->count(5)->create();

    $response = $this->postJson('/api/v1/admin/notifications/read-all', [], $headers);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.marked_count', 5);

    expect(AdminNotificationUserState::query()
        ->where('user_id', (int) $admin->id)
        ->whereNotNull('read_at')
        ->count())->toBe(5);
});

it('deletes a notification', function (): void {
    ['headers' => $headers, 'admin' => $admin] = adminNotificationHeaders();

    $notification = AdminNotification::factory()->create();

    $response = $this->deleteJson("/api/v1/admin/notifications/{$notification->id}", [], $headers);

    $response->assertNoContent();

    $this->assertSoftDeleted('admin_notification_user_states', [
        'admin_notification_id' => (int) $notification->id,
        'user_id' => (int) $admin->id,
    ]);
});

it('shows center-scoped notifications to center admin', function (): void {
    $center = Center::factory()->create();
    $role = Role::firstOrCreate(
        ['slug' => 'super_admin'],
        [
            'name' => 'Super Admin',
            'name_translations' => ['en' => 'Super Admin'],
            'description_translations' => ['en' => 'Full system administrator'],
        ]
    );

    $centerAdmin = User::factory()->create([
        'password' => 'secret123',
        'is_student' => false,
        'center_id' => $center->id,
    ]);
    $centerAdmin->roles()->syncWithoutDetaching([$role->id]);
    $centerAdmin->centers()->syncWithoutDetaching([
        (int) $center->id => ['type' => 'admin'],
    ]);

    ['headers' => $headers] = adminNotificationHeaders($centerAdmin);

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
        ->not->toContain((int) $broadcastNotification->id)
        ->not->toContain((int) $otherCenterNotification->id);
});

it('shows all shared center notifications to system super admin', function (): void {
    ['headers' => $headers] = adminNotificationHeaders();

    $centerA = Center::factory()->create();
    $centerB = Center::factory()->create();

    $centerANotification = AdminNotification::factory()->forCenter($centerA)->create();
    $centerBNotification = AdminNotification::factory()->forCenter($centerB)->create();
    $globalNotification = AdminNotification::factory()->create();

    $response = $this->getJson('/api/v1/admin/notifications', $headers);

    $response->assertOk()
        ->assertJsonPath('success', true);

    $ids = collect((array) $response->json('data'))
        ->pluck('id')
        ->map(static fn ($id): int => (int) $id)
        ->all();

    expect($ids)
        ->toContain((int) $centerANotification->id)
        ->toContain((int) $centerBNotification->id)
        ->toContain((int) $globalNotification->id);
});

it('scopes center admin notifications to their own center for branded and unbranded centers', function (): void {
    $role = Role::firstOrCreate(
        ['slug' => 'super_admin'],
        [
            'name' => 'Super Admin',
            'name_translations' => ['en' => 'Super Admin'],
            'description_translations' => ['en' => 'Full system administrator'],
        ]
    );

    $brandedCenter = Center::factory()->create(['type' => 1]);
    $unbrandedCenter = Center::factory()->create(['type' => 0]);

    $brandedAdmin = User::factory()->create([
        'password' => 'secret123',
        'is_student' => false,
        'center_id' => $brandedCenter->id,
    ]);
    $brandedAdmin->roles()->syncWithoutDetaching([$role->id]);
    $brandedAdmin->centers()->syncWithoutDetaching([(int) $brandedCenter->id => ['type' => 'admin']]);

    $unbrandedAdmin = User::factory()->create([
        'password' => 'secret123',
        'is_student' => false,
        'center_id' => $unbrandedCenter->id,
    ]);
    $unbrandedAdmin->roles()->syncWithoutDetaching([$role->id]);
    $unbrandedAdmin->centers()->syncWithoutDetaching([(int) $unbrandedCenter->id => ['type' => 'admin']]);

    $brandedNotification = AdminNotification::factory()->forCenter($brandedCenter)->create();
    $unbrandedNotification = AdminNotification::factory()->forCenter($unbrandedCenter)->create();
    $globalNotification = AdminNotification::factory()->create();

    ['headers' => $brandedHeaders] = adminNotificationHeaders($brandedAdmin);
    $brandedResponse = $this->getJson('/api/v1/admin/notifications', $brandedHeaders);
    $brandedResponse->assertOk();

    $brandedIds = collect((array) $brandedResponse->json('data'))
        ->pluck('id')
        ->map(static fn ($id): int => (int) $id)
        ->all();

    expect($brandedIds)
        ->toContain((int) $brandedNotification->id)
        ->not->toContain((int) $unbrandedNotification->id)
        ->not->toContain((int) $globalNotification->id);

    ['headers' => $unbrandedHeaders] = adminNotificationHeaders($unbrandedAdmin);
    $unbrandedResponse = $this->getJson('/api/v1/admin/notifications', $unbrandedHeaders);
    $unbrandedResponse->assertOk();

    $unbrandedIds = collect((array) $unbrandedResponse->json('data'))
        ->pluck('id')
        ->map(static fn ($id): int => (int) $id)
        ->all();

    expect($unbrandedIds)
        ->toContain((int) $unbrandedNotification->id)
        ->not->toContain((int) $brandedNotification->id)
        ->not->toContain((int) $globalNotification->id);
});

it('uses extra view request center scope for centerless student requests', function (): void {
    $center = Center::factory()->create([
        'type' => 0,
        'default_view_limit' => 0,
    ]);

    $course = Course::factory()->create([
        'status' => 3,
        'is_published' => true,
        'center_id' => $center->id,
    ]);

    $video = Video::factory()->create([
        'lifecycle_status' => 2,
        'encoding_status' => 3,
    ]);

    CourseVideo::create([
        'course_id' => $course->id,
        'video_id' => $video->id,
        'order_index' => 1,
        'visible' => true,
    ]);

    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => null,
    ]);
    $student->centers()->syncWithoutDetaching([(int) $center->id => ['type' => 'student']]);

    Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'center_id' => $center->id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    app(ExtraViewRequestService::class)->createForStudent(
        $student,
        $center,
        $course,
        $video,
        'Need another attempt'
    );

    $notification = AdminNotification::query()
        ->where('type', AdminNotificationType::EXTRA_VIEW_REQUEST)
        ->latest('id')
        ->firstOrFail();

    expect($notification->center_id)->toBe((int) $center->id);

    $role = Role::firstOrCreate(
        ['slug' => 'super_admin'],
        [
            'name' => 'Super Admin',
            'name_translations' => ['en' => 'Super Admin'],
            'description_translations' => ['en' => 'Full system administrator'],
        ]
    );

    $centerAdmin = User::factory()->create([
        'password' => 'secret123',
        'is_student' => false,
        'center_id' => $center->id,
    ]);
    $centerAdmin->roles()->syncWithoutDetaching([$role->id]);
    $centerAdmin->centers()->syncWithoutDetaching([(int) $center->id => ['type' => 'admin']]);

    ['headers' => $headers] = adminNotificationHeaders($centerAdmin);
    $response = $this->getJson('/api/v1/admin/notifications', $headers);

    $response->assertOk();

    $ids = collect((array) $response->json('data'))
        ->pluck('id')
        ->map(static fn ($id): int => (int) $id)
        ->all();

    expect($ids)->toContain((int) $notification->id);
});

it('shows user-targeted notifications to specific admin', function (): void {
    $role = Role::firstOrCreate(
        ['slug' => 'super_admin'],
        [
            'name' => 'Super Admin',
            'name_translations' => ['en' => 'Super Admin'],
            'description_translations' => ['en' => 'Full system administrator'],
        ]
    );

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

    ['headers' => $headers] = adminNotificationHeaders($targetAdmin);
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
    ['headers' => $headers] = adminNotificationHeaders();

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

it('allows non-super-admin system admin with notification permission to see shared notifications', function (): void {
    $permission = Permission::firstOrCreate(['name' => 'notification.manage'], [
        'description' => 'Manage admin notifications',
    ]);
    $role = Role::firstOrCreate(
        ['slug' => 'ops_admin'],
        [
            'name' => 'Ops Admin',
            'name_translations' => ['en' => 'Ops Admin'],
            'description_translations' => ['en' => 'Operations administrator'],
        ]
    );
    $role->permissions()->syncWithoutDetaching([$permission->id]);

    $admin = User::factory()->create([
        'password' => 'secret123',
        'is_student' => false,
        'center_id' => null,
    ]);
    $admin->roles()->syncWithoutDetaching([$role->id]);

    $centerA = Center::factory()->create();
    $centerB = Center::factory()->create();
    $centerANotification = AdminNotification::factory()->forCenter($centerA)->create();
    $centerBNotification = AdminNotification::factory()->forCenter($centerB)->create();
    $globalNotification = AdminNotification::factory()->create();

    ['headers' => $headers] = adminNotificationHeaders($admin);
    $response = $this->getJson('/api/v1/admin/notifications', $headers);

    $response->assertOk()
        ->assertJsonPath('success', true);

    $ids = collect((array) $response->json('data'))
        ->pluck('id')
        ->map(static fn ($id): int => (int) $id)
        ->all();

    expect($ids)
        ->toContain((int) $centerANotification->id)
        ->toContain((int) $centerBNotification->id)
        ->toContain((int) $globalNotification->id);
});

it('denies notification listing when admin lacks notification permission', function (): void {
    $role = Role::firstOrCreate(
        ['slug' => 'readonly_admin'],
        [
            'name' => 'Read Only Admin',
            'name_translations' => ['en' => 'Read Only Admin'],
            'description_translations' => ['en' => 'No notification permission'],
        ]
    );

    $admin = User::factory()->create([
        'password' => 'secret123',
        'is_student' => false,
        'center_id' => null,
    ]);
    $admin->roles()->syncWithoutDetaching([$role->id]);

    ['headers' => $headers] = adminNotificationHeaders($admin);
    $response = $this->getJson('/api/v1/admin/notifications', $headers);

    $response->assertForbidden()
        ->assertJsonPath('error.code', 'PERMISSION_DENIED');
});
