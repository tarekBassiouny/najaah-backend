<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            'super_admin' => ['description' => 'Full system administrator', 'is_admin_role' => true],
            'content_admin' => ['description' => 'Content administrator', 'is_admin_role' => true],
            'center_admin' => ['description' => 'Center administrator', 'is_admin_role' => true],
            'support_admin' => ['description' => 'Support administrator', 'is_admin_role' => true],
            'center_owner' => ['description' => 'Center owner', 'is_admin_role' => true],
            'content_manager' => ['description' => 'Content manager', 'is_admin_role' => true],
            'student' => ['description' => 'Student', 'is_admin_role' => false],
        ];

        foreach ($roles as $slug => $config) {
            $name = str_replace('_', ' ', $slug);
            Role::updateOrCreate(['slug' => $slug], [
                'name' => $name,
                'name_translations' => [
                    'en' => $name,
                    'ar' => 'دور '.$slug,
                ],
                'slug' => $slug,
                'description_translations' => [
                    'en' => $config['description'],
                    'ar' => 'وصف '.$slug,
                ],
                'is_admin_role' => $config['is_admin_role'],
            ]);
        }
    }
}
