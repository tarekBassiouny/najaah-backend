<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $superAdminRole = Role::query()->where('slug', 'super_admin')->first();
        if (! $superAdminRole instanceof Role) {
            throw new RuntimeException('Role "super_admin" is required before running UserSeeder.');
        }

        $email = (string) env('SEED_ADMIN_EMAIL', 'admin@example.com');
        $password = (string) env('SEED_ADMIN_PASSWORD', 'admin123');
        $phone = (string) env('SEED_ADMIN_PHONE', '1999000000');
        $countryCode = (string) env('SEED_ADMIN_COUNTRY_CODE', '+20');

        $admin = User::query()->firstOrCreate(
            ['email' => $email, 'is_student' => false],
            [
                'name' => 'System Admin',
                'phone' => $phone,
                'country_code' => $countryCode,
                'password' => Hash::make($password),
                'center_id' => null,
                'status' => 1,
            ]
        );

        $admin->roles()->syncWithoutDetaching([$superAdminRole->id]);
    }
}
