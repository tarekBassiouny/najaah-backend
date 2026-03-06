<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\EducationalStage;
use App\Models\Center;
use App\Models\Grade;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class GradeFactory extends Factory
{
    protected $model = Grade::class;

    public function definition(): array
    {
        $name = 'Grade '.fake()->numberBetween(1, 12);

        return [
            'center_id' => Center::factory(),
            'name_translations' => [
                'en' => $name,
                'ar' => $name,
            ],
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1000, 9999),
            'stage' => fake()->randomElement(EducationalStage::cases())->value,
            'order' => fake()->numberBetween(1, 20),
            'is_active' => true,
        ];
    }
}
