<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            'super_admin' => 'Full system administrator',
            'content_admin' => 'Content administrator',
            'center_admin' => 'Center administrator',
            'support_admin' => 'Support administrator',
            'center_owner' => 'Center owner',
            'content_manager' => 'Content manager',
            'student' => 'Student',
        ];

        foreach ($roles as $slug => $descEn) {
            $name = str_replace('_', ' ', $slug);
            Role::updateOrCreate(['slug' => $slug], [
                'name' => $name,
                'name_translations' => [
                    'en' => $name,
                    'ar' => 'دور '.$slug,
                ],
                'slug' => $slug,
                'description_translations' => [
                    'en' => $descEn,
                    'ar' => 'وصف '.$slug,
                ],
            ]);
        }
    }
}
