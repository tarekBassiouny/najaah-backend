<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class RoleFactory extends Factory
{
    protected $model = Role::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->randomElement([
            'admin',
            'instructor',
            'student',
            'manager',
            'support',
        ]);

        return [
            'name' => ucfirst($name),
            'name_translations' => [
                'en' => ucfirst($name),
                'ar' => 'دور '.ucfirst($name),
            ],
            'slug' => Str::slug($name),
            'description_translations' => [
                'en' => ucfirst($name).' role',
                'ar' => 'دور '.$name,
            ],
        ];
    }
}
