<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'admin.manage' => 'Manage admin users',
            'role.manage' => 'Manage roles and permissions',
            'permission.view' => 'View permission catalog',
            'course.manage' => 'Manage courses',
            'course.publish' => 'Publish courses',
            'section.manage' => 'Manage sections',
            'analytics.manage' => 'Manage analytics dashboards',
            'video.manage' => 'Manage videos',
            'video.upload' => 'Authorize video uploads',
            'video.playback.override' => 'Override playback restrictions',
            'pdf.manage' => 'Manage PDFs',
            'enrollment.manage' => 'Manage enrollments',
            'center.manage' => 'Manage centers',
            'settings.manage' => 'Manage settings',
            'settings.view' => 'View settings',
            'student.manage' => 'Manage student accounts',
            'survey.manage' => 'Manage surveys',
            'audit.view' => 'View analytics and audit logs',
            'notification.manage' => 'Manage admin notifications',
            'device_change.manage' => 'Manage device change requests',
            'extra_view.manage' => 'Manage extra view requests',
            'instructor.manage' => 'Manage instructors',
            'agent.execute' => 'Run automated agents',
            'agent.content_publishing' => 'Execute content publishing agents',
            'agent.enrollment.bulk' => 'Execute bulk enrollment agents',
        ];

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        Permission::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        foreach ($permissions as $name => $description) {
            Permission::updateOrCreate(['name' => $name], [
                'description' => $description,
            ]);
        }
    }
}
