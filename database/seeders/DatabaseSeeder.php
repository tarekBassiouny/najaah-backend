<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            PermissionSeeder::class,
            RolePermissionSeeder::class,
            UserSeeder::class,
            SystemSettingSeeder::class,
        ]);

        if ($this->shouldSeedDemoData()) {
            $this->call([
                DemoDataSeeder::class,
            ]);
        }
    }

    private function shouldSeedDemoData(): bool
    {
        return filter_var((string) env('SEED_DEMO_DATA', 'false'), FILTER_VALIDATE_BOOL) === true;
    }
}
