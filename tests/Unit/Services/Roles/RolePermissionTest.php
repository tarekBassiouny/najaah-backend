<?php

declare(strict_types=1);

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class)->group('roles', 'services', 'admin');

it('checks role membership by slug or name', function (): void {
    $role = Role::factory()->create([
        'slug' => 'content_admin',
        'name' => 'content admin',
    ]);
    $user = User::factory()->create(['is_student' => false]);
    $user->roles()->attach($role);

    expect($user->hasRole('content_admin'))->toBeTrue()
        ->and($user->hasRole('content admin'))->toBeTrue()
        ->and($user->hasRole('missing_role'))->toBeFalse();
});

it('resolves permissions through roles', function (): void {
    $permission = Permission::factory()->create([
        'name' => 'course.manage',
    ]);
    $role = Role::factory()->create([
        'slug' => 'content_admin',
        'name' => 'content admin',
    ]);
    $role->permissions()->attach($permission);

    $user = User::factory()->create(['is_student' => false]);
    $user->roles()->attach($role);

    expect($user->hasPermission('course.manage'))->toBeTrue()
        ->and($user->hasPermission('video.manage'))->toBeFalse();
});
