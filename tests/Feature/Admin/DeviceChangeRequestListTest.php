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

it('allows super admin to list requests for specific center', function (): void {
    $super = $this->asAdmin();

    $centerA = Center::factory()->create();
    $centerB = Center::factory()->create();

    DeviceChangeRequest::factory()->create(['center_id' => $centerA->id]);
    DeviceChangeRequest::factory()->create(['center_id' => $centerB->id]);

    // Super admin can access any center's device change requests via center route
    $responseA = $this->actingAs($super, 'admin')->getJson("/api/v1/admin/centers/{$centerA->id}/device-change-requests", $this->adminHeaders());
    $responseA->assertOk()->assertJsonCount(1, 'data');

    $responseB = $this->actingAs($super, 'admin')->getJson("/api/v1/admin/centers/{$centerB->id}/device-change-requests", $this->adminHeaders());
    $responseB->assertOk()->assertJsonCount(1, 'data');
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

    // Center admin can access their own center's device change requests
    $response = $this->getJson("/api/v1/admin/centers/{$centerA->id}/device-change-requests", [
        'Authorization' => 'Bearer '.$token,
        'Accept' => 'application/json',
        'X-Api-Key' => config('services.system_api_key'),
    ]);

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.center_id', $centerA->id);

    // Center admin cannot access other center's device change requests
    $blocked = $this->getJson("/api/v1/admin/centers/{$centerB->id}/device-change-requests", [
        'Authorization' => 'Bearer '.$token,
        'Accept' => 'application/json',
        'X-Api-Key' => config('services.system_api_key'),
    ]);

    $blocked->assertForbidden();
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

    $response = $this->actingAs($super, 'admin')->getJson("/api/v1/admin/centers/{$center->id}/device-change-requests?status=".DeviceChangeRequest::STATUS_APPROVED->value.'&user_id='.$user->id.'&date_from='.now()->subDays(3)->toDateString().'&per_page=1', $this->adminHeaders());

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('meta.per_page', 1);
});

it('rejects access without permission', function (): void {
    $center = Center::factory()->create();
    $admin = User::factory()->create([
        'password' => 'secret123',
        'is_student' => false,
        'center_id' => $center->id,
    ]);

    $token = (string) Auth::guard('admin')->attempt([
        'email' => $admin->email,
        'password' => 'secret123',
        'is_student' => false,
    ]);

    $response = $this->getJson("/api/v1/admin/centers/{$center->id}/device-change-requests", [
        'Authorization' => 'Bearer '.$token,
        'Accept' => 'application/json',
        'X-Api-Key' => config('services.system_api_key'),
    ]);

    $response->assertForbidden();
});
