<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = Permission::all()->keyBy('name');

        $assignments = [
            'super_admin' => array_keys($permissions->all()),
            'content_admin' => [
                'course.manage',
                'course.publish',
                'section.manage',
                'video.manage',
                'video.upload',
                'pdf.manage',
                'instructor.manage',
            ],
            'center_admin' => [
                'course.manage',
                'section.manage',
                'video.manage',
                'pdf.manage',
                'enrollment.manage',
                'extra_view.manage',
                'device_change.manage',
            ],
            'support_admin' => [
                'audit.view',
                'extra_view.manage',
                'device_change.manage',
            ],
        ];

        foreach ($assignments as $roleSlug => $permissionNames) {
            $role = Role::where('slug', $roleSlug)->first();

            if ($role === null) {
                continue;
            }

            $permissionIds = collect($permissionNames)
                ->map(fn (string $name) => $permissions[$name]?->id)
                ->filter()
                ->values()
                ->all();

            $role->permissions()->sync($permissionIds);
        }
    }
}
