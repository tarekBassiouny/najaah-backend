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
                'en' => 'Section: Title',
                'ar' => 'قسم: العنوان',
            ],
            'description_translations' => [
                'en' => 'Description',
                'ar' => 'وصف',
            ],
            'order_index' => 1,
            'visible' => true,
        ];
    }
}
