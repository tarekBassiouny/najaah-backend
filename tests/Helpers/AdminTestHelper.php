<?php

declare(strict_types=1);

namespace Tests\Helpers;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

trait AdminTestHelper
{
    public ?string $adminToken;

    private function ensureSuperAdminRole(): Role
    {
        $role = Role::firstOrCreate(['slug' => 'super_admin'], [
            'name' => 'super admin',
            'name_translations' => [
                'en' => 'super admin',
                'ar' => 'super admin',
            ],
            'description_translations' => [
                'en' => 'Full system administrator',
                'ar' => 'Full system administrator',
            ],
        ]);

        $permissions = [
            'admin.manage',
            'role.manage',
            'permission.view',
            'course.manage',
            'course.publish',
            'section.manage',
            'video.manage',
            'video.upload',
            'video.playback.override',
            'pdf.manage',
            'enrollment.manage',
            'center.manage',
            'settings.manage',
            'audit.view',
            'device_change.manage',
            'extra_view.manage',
            'instructor.manage',
        ];

        $permissionIds = [];
        foreach ($permissions as $name) {
            $permission = Permission::firstOrCreate(['name' => $name], [
                'description' => 'Permission: '.$name,
            ]);
            $permissionIds[] = $permission->id;
        }

        $role->permissions()->sync($permissionIds);

        return $role;
    }

    public function asAdmin(): User
    {
        /** @var User $admin */
        $admin = User::factory()->create([
            'password' => 'secret123',
            'is_student' => false,
            'center_id' => null,
        ]);

        $role = $this->ensureSuperAdminRole();
        $admin->roles()->syncWithoutDetaching([$role->id]);

        $this->adminToken = (string) Auth::guard('admin')->attempt([
            'email' => $admin->email,
            'password' => 'secret123',
            'is_student' => false,
        ]);

        return $admin;
    }

    public function adminHeaders(array $extra = []): array
    {
        return array_merge([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer '.$this->adminToken,
        ], $extra);
    }
}
