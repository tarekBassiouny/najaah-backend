<?php

namespace Database\Seeders;

use App\Models\Center;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    private function nextPhone(int &$counter): string
    {
        return '1'.str_pad((string) $counter++, 9, '0', STR_PAD_LEFT);
    }

    public function run(): void
    {
        $phoneCounter = 1;

        // Seed admins/super admins across centers
        $superAdmin = User::factory()->create([
            'name' => 'System Admin',
            'phone' => '10000000000',
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
        $centers = Center::all();
        foreach ($centers as $center) {
            $owner = User::factory()->create([
                'center_id' => $center->id,
                'is_student' => false,
                'phone' => $this->nextPhone($phoneCounter),
            ]);
            $owner->roles()->attach(Role::where('slug', 'center_owner')->value('id'));

            $admin = User::factory()->create([
                'center_id' => $center->id,
                'is_student' => false,
                'phone' => $this->nextPhone($phoneCounter),
            ]);
            $admin->roles()->attach(Role::where('slug', 'center_admin')->value('id'));
        }

        // Students for each center
        foreach ($centers as $center) {
            for ($i = 0; $i < 2; $i++) {
                $student = User::factory()->create([
                    'center_id' => $center->id,
                    'is_student' => true,
                    'phone' => $this->nextPhone($phoneCounter),
                ]);

                $student->roles()->attach(Role::where('slug', 'student')->value('id'));
            }
        }
    }
}
