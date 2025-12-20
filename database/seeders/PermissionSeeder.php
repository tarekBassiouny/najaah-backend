<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'admin.manage' => 'Manage admin users',
            'role.manage' => 'Manage roles',
            'permission.view' => 'View permissions',
            'course.manage' => 'Manage courses',
            'course.publish' => 'Publish courses',
            'section.manage' => 'Manage sections',
            'video.manage' => 'Manage videos',
            'video.upload' => 'Authorize video uploads',
            'video.playback.override' => 'Override playback restrictions',
            'pdf.manage' => 'Manage PDFs',
            'enrollment.manage' => 'Manage enrollments',
            'center.manage' => 'Manage centers',
            'settings.manage' => 'Manage settings',
            'audit.view' => 'View audit logs',
            'device_change.manage' => 'Manage device change requests',
            'extra_view.manage' => 'Manage extra view requests',
            'instructor.manage' => 'Manage instructors',
        ];

        foreach ($permissions as $name => $description) {
            Permission::updateOrCreate(['name' => $name], [
                'description' => $description,
            ]);
        }
    }
}
