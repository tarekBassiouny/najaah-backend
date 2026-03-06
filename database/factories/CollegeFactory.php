<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Center;
use App\Models\College;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CollegeFactory extends Factory
{
    protected $model = College::class;

    public function definition(): array
    {
        $name = fake()->company().' University';

        return [
            'center_id' => Center::factory(),
            'name_translations' => [
                'en' => $name,
                'ar' => $name,
            ],
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1000, 9999),
            'type' => fake()->numberBetween(0, 5),
            'address' => fake()->address(),
            'is_active' => true,
        ];
    }
}
