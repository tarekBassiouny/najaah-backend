<?php

declare(strict_types=1);

use App\Models\Center;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\Helpers\AdminTestHelper;

uses(RefreshDatabase::class, AdminTestHelper::class)->group('admin-roles', 'admin', 'center');

function createCenterOwnerAuth(Center $center): array
{
    $role = Role::firstOrCreate(['slug' => 'center_owner'], [
        'name' => 'center owner',
        'name_translations' => [
            'en' => 'center owner',
            'ar' => 'center owner',
        ],
        'description_translations' => [
            'en' => 'Center owner',
            'ar' => 'Center owner',
        ],
        'is_admin_role' => true,
    ]);

    $permissions = ['role.manage', 'admin.manage'];
    $permissionIds = [];

    foreach ($permissions as $permissionName) {
        $permission = Permission::firstOrCreate(['name' => $permissionName], [
            'description' => 'Permission: '.$permissionName,
        ]);
        $permissionIds[] = $permission->id;
    }

    $role->permissions()->syncWithoutDetaching($permissionIds);

    $owner = User::factory()->create([
        'password' => 'secret123',
        'is_student' => false,
        'center_id' => $center->id,
    ]);

    $owner->roles()->syncWithoutDetaching([$role->id]);

    $token = (string) Auth::guard('admin')->attempt([
        'email' => $owner->email,
        'password' => 'secret123',
        'is_student' => false,
    ]);

    return [
        'Authorization' => 'Bearer '.$token,
        'Accept' => 'application/json',
        'X-Api-Key' => $center->api_key,
    ];
}

it('allows center owner to manage roles in center scope', function (): void {
    $center = Center::factory()->create();
    $headers = createCenterOwnerAuth($center);

    $storeResponse = $this->postJson('/api/v1/admin/centers/'.$center->id.'/roles', [
        'name_translations' => [
            'en' => 'Content Manager',
            'ar' => 'مدير المحتوى',
        ],
        'slug' => 'content_manager',
        'description_translations' => [
            'en' => 'Manages center content.',
        ],
    ], $headers);

    $storeResponse->assertCreated()
        ->assertJsonPath('data.slug', 'content_manager')
        ->assertJsonPath('data.center_id', (int) $center->id);

    $listResponse = $this->getJson('/api/v1/admin/centers/'.$center->id.'/roles', $headers);

    $listResponse->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.slug', 'content_manager');
});

it('allows same role slug across different centers', function (): void {
    $this->asAdmin();

    $centerA = Center::factory()->create();
    $centerB = Center::factory()->create();

    $payload = [
        'name_translations' => ['en' => 'Content Manager'],
        'slug' => 'content_manager',
    ];

    $this->postJson('/api/v1/admin/centers/'.$centerA->id.'/roles', $payload, $this->adminHeaders())
        ->assertCreated()
        ->assertJsonPath('data.center_id', (int) $centerA->id);

    $this->postJson('/api/v1/admin/centers/'.$centerB->id.'/roles', $payload, $this->adminHeaders())
        ->assertCreated()
        ->assertJsonPath('data.center_id', (int) $centerB->id);
});

it('returns not found when center role does not belong to route center', function (): void {
    $this->asAdmin();

    $centerA = Center::factory()->create();
    $centerB = Center::factory()->create();

    $role = Role::factory()->create([
        'center_id' => $centerB->id,
        'slug' => 'center_b_manager',
    ]);

    $response = $this->putJson('/api/v1/admin/centers/'.$centerA->id.'/roles/'.$role->id, [
        'name_translations' => ['en' => 'Updated Name'],
    ], $this->adminHeaders());

    $response->assertStatus(404)
        ->assertJsonPath('error.code', 'NOT_FOUND');
});

it('allows center owner to sync admin roles in center scope', function (): void {
    $center = Center::factory()->create();
    $headers = createCenterOwnerAuth($center);

    $role = Role::factory()->create([
        'center_id' => $center->id,
        'slug' => 'center_ops',
        'name_translations' => ['en' => 'Center Ops'],
    ]);

    $admin = User::factory()->create([
        'password' => 'secret123',
        'is_student' => false,
        'center_id' => $center->id,
    ]);

    $response = $this->putJson('/api/v1/admin/centers/'.$center->id.'/users/'.$admin->id.'/roles', [
        'role_ids' => [$role->id],
    ], $headers);

    $response->assertOk()
        ->assertJsonPath('data.roles.0', 'center_ops');
});
