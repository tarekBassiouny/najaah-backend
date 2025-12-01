<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Course;
use App\Models\Section;
use Illuminate\Database\Eloquent\Factories\Factory;

class SectionFactory extends Factory
{
    protected $model = Section::class;

    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'title_translations' => [
                'en' => 'Section: '.$this->faker->sentence(),
                'ar' => 'قسم: '.$this->faker->sentence(),
            ],
            'description_translations' => [
                'en' => $this->faker->paragraph(),
                'ar' => 'وصف: '.$this->faker->sentence(),
            ],
            'order_index' => $this->faker->numberBetween(1, 20),
            'visible' => true,
        ];
    }
}
