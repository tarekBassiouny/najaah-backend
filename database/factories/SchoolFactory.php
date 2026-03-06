<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\SchoolType;
use App\Models\Center;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SchoolFactory extends Factory
{
    protected $model = School::class;

    public function definition(): array
    {
        $name = fake()->company().' School';

        return [
            'center_id' => Center::factory(),
            'name_translations' => [
                'en' => $name,
                'ar' => $name,
            ],
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1000, 9999),
            'type' => fake()->randomElement(SchoolType::cases())->value,
            'address' => fake()->address(),
            'is_active' => true,
        ];
    }
}
