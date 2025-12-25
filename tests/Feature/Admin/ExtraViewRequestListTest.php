<?php

declare(strict_types=1);

use App\Models\Center;
use App\Models\ExtraViewRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class)->group('admin', 'extra-view-requests');

it('allows super admin to list requests across centers', function (): void {
    $super = $this->asAdmin();

    $centerA = Center::factory()->create();
    $centerB = Center::factory()->create();

    ExtraViewRequest::factory()->create(['center_id' => $centerA->id]);
    ExtraViewRequest::factory()->create(['center_id' => $centerB->id]);

    $response = $this->actingAs($super, 'admin')->getJson('/api/v1/admin/extra-view-requests', $this->adminHeaders());

    $response->assertOk()
        ->assertJsonCount(2, 'data');
});

it('scopes list to admin center', function (): void {
    $permission = Permission::factory()->create(['name' => 'extra_view.manage']);
    $role = Role::factory()->create(['slug' => 'extra_view_admin']);
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

    ExtraViewRequest::factory()->create(['center_id' => $centerA->id]);
    ExtraViewRequest::factory()->create(['center_id' => $centerB->id]);

    $token = (string) Auth::guard('admin')->attempt([
        'email' => $admin->email,
        'password' => 'secret123',
        'is_student' => false,
    ]);

    $response = $this->getJson('/api/v1/admin/extra-view-requests', [
        'Authorization' => 'Bearer '.$token,
        'Accept' => 'application/json',
        'X-Api-Key' => config('services.system_api_key'),
    ]);

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.center_id', $centerA->id);
});

it('applies filters and pagination', function (): void {
    $super = $this->asAdmin();
    $center = Center::factory()->create();
    $user = User::factory()->create();

    ExtraViewRequest::factory()->create([
        'center_id' => $center->id,
        'user_id' => $user->id,
        'status' => ExtraViewRequest::STATUS_APPROVED,
        'created_at' => now()->subDays(2),
    ]);
    ExtraViewRequest::factory()->create([
        'center_id' => $center->id,
        'status' => ExtraViewRequest::STATUS_PENDING,
        'created_at' => now()->subDays(10),
    ]);

    $response = $this->actingAs($super, 'admin')->getJson('/api/v1/admin/extra-view-requests?status='.ExtraViewRequest::STATUS_APPROVED.'&user_id='.$user->id.'&date_from='.now()->subDays(3)->toDateString().'&per_page=1', $this->adminHeaders());

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

    $response = $this->getJson('/api/v1/admin/extra-view-requests', [
        'Authorization' => 'Bearer '.$token,
        'Accept' => 'application/json',
        'X-Api-Key' => config('services.system_api_key'),
    ]);

    $response->assertForbidden();
});
