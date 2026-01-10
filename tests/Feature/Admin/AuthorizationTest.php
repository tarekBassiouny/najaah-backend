<?php

declare(strict_types=1);

use App\Models\Center;
use App\Models\Course;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class)->group('auth', 'authorization');

it('blocks students from admin endpoints', function (): void {
    $student = User::factory()->create([
        'password' => 'secret123',
        'is_student' => true,
    ]);

    $token = (string) Auth::guard('api')->attempt([
        'email' => $student->email,
        'password' => 'secret123',
        'is_student' => true,
    ]);

    $response = $this->postJson('/api/v1/admin/centers', [
        'slug' => 'blocked-center',
        'type' => 'unbranded',
        'name' => 'Blocked Center',
        'admin' => [
            'name' => 'Owner',
            'email' => 'blocked-owner@example.com',
        ],
    ], [
        'Authorization' => 'Bearer '.$token,
        'Accept' => 'application/json',
        'X-Api-Key' => config('services.system_api_key'),
    ]);

    $response->assertStatus(401);
});

it('prevents admins from accessing other centers', function (): void {
    $permission = Permission::factory()->create(['name' => 'course.manage']);
    $role = Role::factory()->create(['slug' => 'content_admin']);
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

    $token = (string) Auth::guard('admin')->attempt([
        'email' => $admin->email,
        'password' => 'secret123',
        'is_student' => false,
    ]);

    $course = Course::factory()->create(['center_id' => $centerB->id]);

    $response = $this->getJson("/api/v1/admin/centers/{$centerA->id}/courses/{$course->id}", [
        'Authorization' => 'Bearer '.$token,
        'Accept' => 'application/json',
        'X-Api-Key' => config('services.system_api_key'),
    ]);

    $response->assertNotFound()
        ->assertJsonPath('error.code', 'NOT_FOUND');
});

it('allows admins with permission and center access', function (): void {
    $permission = Permission::factory()->create(['name' => 'course.manage']);
    $role = Role::factory()->create(['slug' => 'content_admin']);
    $role->permissions()->sync([$permission->id]);

    $center = Center::factory()->create();
    $admin = User::factory()->create([
        'password' => 'secret123',
        'is_student' => false,
        'center_id' => $center->id,
    ]);
    $admin->roles()->sync([$role->id]);
    $admin->centers()->sync([$center->id => ['type' => 'admin']]);

    $token = (string) Auth::guard('admin')->attempt([
        'email' => $admin->email,
        'password' => 'secret123',
        'is_student' => false,
    ]);

    $course = Course::factory()->create(['center_id' => $center->id]);

    $response = $this->getJson("/api/v1/admin/centers/{$center->id}/courses/{$course->id}", [
        'Authorization' => 'Bearer '.$token,
        'Accept' => 'application/json',
        'X-Api-Key' => config('services.system_api_key'),
    ]);

    $response->assertOk();
});

it('restricts center management to super admins', function (): void {
    $permission = Permission::factory()->create(['name' => 'center.manage']);
    $role = Role::factory()->create(['slug' => 'center_admin']);
    $role->permissions()->sync([$permission->id]);

    $admin = User::factory()->create([
        'password' => 'secret123',
        'is_student' => false,
    ]);
    $admin->roles()->sync([$role->id]);

    $token = (string) Auth::guard('admin')->attempt([
        'email' => $admin->email,
        'password' => 'secret123',
        'is_student' => false,
    ]);

    $forbidden = $this->getJson('/api/v1/admin/centers', [
        'Authorization' => 'Bearer '.$token,
        'Accept' => 'application/json',
        'X-Api-Key' => config('services.system_api_key'),
    ]);

    $forbidden->assertForbidden();

    $super = $this->asAdmin();
    $allowed = $this->getJson('/api/v1/admin/centers', [
        'Authorization' => 'Bearer '.$this->adminToken,
        'Accept' => 'application/json',
        'X-Api-Key' => config('services.system_api_key'),
    ]);

    $allowed->assertOk();
});
