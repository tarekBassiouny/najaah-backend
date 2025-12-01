<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Seed admins/super admins across centers
        $superAdmin = User::factory()->create([
            'name' => 'System Admin',
            'phone' => '+10000000000',
            'email' => 'admin@example.com',
            'password' => 'admin123',
            'center_id' => null,
            'is_student' => false,
            'status' => 1,
        ]);

        $superAdmin->roles()->attach(
            Role::where('slug', 'super_admin')->value('id')
        );

        // Center owners/admins
        $centers = \App\Models\Center::all();
        foreach ($centers as $center) {
            $owner = User::factory()->create([
                'center_id' => $center->id,
                'is_student' => false,
            ]);
            $owner->roles()->attach(Role::where('slug', 'center_owner')->value('id'));

            $admin = User::factory()->create([
                'center_id' => $center->id,
                'is_student' => false,
            ]);
            $admin->roles()->attach(Role::where('slug', 'center_admin')->value('id'));
        }

        // Students for each center
        foreach ($centers as $center) {
            User::factory()
                ->count(25)
                ->create([
                    'center_id' => $center->id,
                    'is_student' => true,
                ])
                ->each(function (User $student): void {
                    $student->roles()->attach(Role::where('slug', 'student')->value('id'));
                });
        }
    }
}
