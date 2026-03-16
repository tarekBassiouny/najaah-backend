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
        /** @var array<string, string> $permissions */
        $permissions = config('permissions', []);

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
