<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Center;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSampleSeeder extends Seeder
{
    private function nextPhone(int &$counter): string
    {
        return '1'.str_pad((string) $counter++, 9, '0', STR_PAD_LEFT);
    }

    public function run(): void
    {
        $phoneCounter = 1;
        $centers = Center::query()->get();

        $ownerRoleId = Role::query()->where('slug', 'center_owner')->value('id');
        $centerAdminRoleId = Role::query()->where('slug', 'center_admin')->value('id');
        $studentRoleId = Role::query()->where('slug', 'student')->value('id');

        foreach ($centers as $center) {
            $owner = User::factory()->create([
                'center_id' => $center->id,
                'is_student' => false,
                'phone' => $this->nextPhone($phoneCounter),
            ]);
            if (is_numeric($ownerRoleId)) {
                $owner->roles()->syncWithoutDetaching([(int) $ownerRoleId]);
            }

            $admin = User::factory()->create([
                'center_id' => $center->id,
                'is_student' => false,
                'phone' => $this->nextPhone($phoneCounter),
            ]);
            if (is_numeric($centerAdminRoleId)) {
                $admin->roles()->syncWithoutDetaching([(int) $centerAdminRoleId]);
            }
        }

        foreach ($centers as $center) {
            for ($i = 0; $i < 2; $i++) {
                $student = User::factory()->create([
                    'center_id' => $center->id,
                    'is_student' => true,
                    'phone' => $this->nextPhone($phoneCounter),
                ]);

                if (is_numeric($studentRoleId)) {
                    $student->roles()->syncWithoutDetaching([(int) $studentRoleId]);
                }
            }
        }
    }
}
