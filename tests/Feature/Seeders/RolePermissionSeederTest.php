<?php

declare(strict_types=1);

use App\Models\Permission;
use App\Models\Role;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('seeders', 'roles');

it('assigns center owner permissions equal to super admin', function (): void {
    $this->seed([
        RoleSeeder::class,
        PermissionSeeder::class,
        RolePermissionSeeder::class,
    ]);

    $superAdmin = Role::query()->where('slug', 'super_admin')->firstOrFail();
    $centerOwner = Role::query()->where('slug', 'center_owner')->firstOrFail();

    $superPermissions = $superAdmin->permissions()
        ->pluck('permissions.name')
        ->sort()
        ->values()
        ->all();

    $centerOwnerPermissions = $centerOwner->permissions()
        ->pluck('permissions.name')
        ->sort()
        ->values()
        ->all();

    expect($centerOwnerPermissions)
        ->not->toBeEmpty()
        ->toBe($superPermissions);
});

it('seeds video access manage permission and assigns it to operational admin roles', function (): void {
    $this->seed([
        RoleSeeder::class,
        PermissionSeeder::class,
        RolePermissionSeeder::class,
    ]);

    $permission = Permission::query()->where('name', 'video_access.manage')->first();

    expect($permission)->not->toBeNull()
        ->and($permission?->description)->toBe('Manage video access approvals and codes');

    $centerAdmin = Role::query()->where('slug', 'center_admin')->firstOrFail();
    $supportAdmin = Role::query()->where('slug', 'support_admin')->firstOrFail();

    expect($centerAdmin->permissions()->where('name', 'video_access.manage')->exists())->toBeTrue()
        ->and($supportAdmin->permissions()->where('name', 'video_access.manage')->exists())->toBeTrue();
});
