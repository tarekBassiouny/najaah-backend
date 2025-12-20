<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Permission;
use Illuminate\Database\Eloquent\Factories\Factory;

class PermissionFactory extends Factory
{
    protected $model = Permission::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->randomElement([
            'course.manage',
            'video.manage',
            'enrollment.manage',
            'admin.manage',
            'role.manage',
        ]);

        return [
            'name' => $name,
            'description' => 'Permission: '.$name,
        ];
    }
}
