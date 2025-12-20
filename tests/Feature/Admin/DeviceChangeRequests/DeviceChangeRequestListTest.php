<?php

declare(strict_types=1);

use App\Models\Center;
use App\Models\DeviceChangeRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class)->group('admin', 'device-change-requests');

it('allows super admin to list requests across centers', function (): void {
    $super = $this->asAdmin();

    $centerA = Center::factory()->create();
    $centerB = Center::factory()->create();

    DeviceChangeRequest::factory()->create(['center_id' => $centerA->id]);
    DeviceChangeRequest::factory()->create(['center_id' => $centerB->id]);

    $response = $this->actingAs($super, 'admin')->getJson('/api/v1/admin/device-change-requests');

    $response->assertOk()
        ->assertJsonCount(2, 'data');
});

it('scopes list to admin center', function (): void {
    $permission = Permission::factory()->create(['name' => 'device_change.manage']);
    $role = Role::factory()->create(['slug' => 'device_change_admin']);
    $role->permissions()->sync([$permission->id]);

    $centerA = Center::factory()->create();
    $centerB = Center::factory()->create();

    $admin = User::factory()->create([
        'password' => 'secret123',
        'is_student' => false,
        'center_id' => $centerA->id,
    ]);
    $admin->roles()->sync([$role->id]);
    $admin->centers()->sync([$centerA->id => ['type' => 'admin']]);

    DeviceChangeRequest::factory()->create(['center_id' => $centerA->id]);
    DeviceChangeRequest::factory()->create(['center_id' => $centerB->id]);

    $token = (string) Auth::guard('admin')->attempt([
        'email' => $admin->email,
        'password' => 'secret123',
        'is_student' => false,
    ]);

    $response = $this->getJson('/api/v1/admin/device-change-requests', [
        'Authorization' => 'Bearer '.$token,
        'Accept' => 'application/json',
    ]);

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.center_id', $centerA->id);
});

it('applies filters and pagination', function (): void {
    $super = $this->asAdmin();
    $center = Center::factory()->create();
    $user = User::factory()->create();

    DeviceChangeRequest::factory()->create([
        'center_id' => $center->id,
        'user_id' => $user->id,
        'status' => DeviceChangeRequest::STATUS_APPROVED,
        'created_at' => now()->subDays(2),
    ]);
    DeviceChangeRequest::factory()->create([
        'center_id' => $center->id,
        'status' => DeviceChangeRequest::STATUS_PENDING,
        'created_at' => now()->subDays(10),
    ]);

    $response = $this->actingAs($super, 'admin')->getJson('/api/v1/admin/device-change-requests?status='.DeviceChangeRequest::STATUS_APPROVED.'&user_id='.$user->id.'&date_from='.now()->subDays(3)->toDateString().'&per_page=1');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('meta.per_page', 1);
});

it('rejects access without permission', function (): void {
    $admin = User::factory()->create([
        'password' => 'secret123',
        'is_student' => false,
    ]);

    $token = (string) Auth::guard('admin')->attempt([
        'email' => $admin->email,
        'password' => 'secret123',
        'is_student' => false,
    ]);

    $response = $this->getJson('/api/v1/admin/device-change-requests', [
        'Authorization' => 'Bearer '.$token,
        'Accept' => 'application/json',
    ]);

    $response->assertForbidden();
});
