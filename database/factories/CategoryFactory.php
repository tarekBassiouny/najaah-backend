<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        return [
            'title_translations' => [
                'en' => $this->faker->word(),
                'ar' => 'فئة '.$this->faker->word(),
            ],
            'description_translations' => [
                'en' => $this->faker->sentence(),
                'ar' => 'وصف: '.$this->faker->sentence(),
            ],
            'parent_id' => null,
            'order_index' => $this->faker->numberBetween(1, 50),
            'is_active' => true,
        ];
    }
}
