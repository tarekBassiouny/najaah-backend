<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            'SUPER_ADMIN' => 'Full system administrator',
            'CENTER_OWNER' => 'Center owner',
            'CENTER_ADMIN' => 'Center admin',
            'CONTENT_MANAGER' => 'Content manager',
            'STUDENT' => 'Student',
        ];

        foreach ($roles as $slug => $descEn) {
            Role::create([
                'name' => str_replace('_', ' ', strtolower($slug)),
                'name_translations' => [
                    'en' => str_replace('_', ' ', strtolower($slug)),
                    'ar' => 'دور '.$slug,
                ],
                'slug' => strtolower($slug),
                'description_translations' => [
                    'en' => $descEn,
                    'ar' => 'وصف '.$slug,
                ],
            ]);
        }
    }
}
