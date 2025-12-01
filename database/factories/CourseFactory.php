<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Category;
use App\Models\Center;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CourseFactory extends Factory
{
    protected $model = Course::class;

    public function definition(): array
    {
        $center = Center::factory();

        return [
            'center_id' => $center,
            'category_id' => Category::factory(),
            'title_translations' => [
                'en' => $this->faker->sentence(),
                'ar' => 'دورة '.$this->faker->word(),
            ],
            'description_translations' => [
                'en' => $this->faker->paragraph(),
                'ar' => 'وصف: '.$this->faker->sentence(),
            ],
            'instructor_translations' => [
                'en' => $this->faker->name(),
                'ar' => 'المدرب '.$this->faker->name(),
            ],
            'college_translations' => [
                'en' => $this->faker->company().' College',
                'ar' => 'كلية '.$this->faker->company(),
            ],
            'grade_year' => (string) $this->faker->numberBetween(1, 4),
            'thumbnail_url' => $this->faker->imageUrl(640, 360),
            'difficulty_level' => $this->faker->numberBetween(0, 2),
            'language' => $this->faker->randomElement(['en', 'ar', 'de']),
            'course_code' => 'COURSE-'.$this->faker->unique()->numerify('####'),
            'tags' => [
                'module' => $this->faker->word(),
                'type' => $this->faker->randomElement(['intro', 'part', 'qna']),
            ],
            'status' => $this->faker->numberBetween(0, 4),
            'is_published' => $this->faker->boolean(),
            'duration_minutes' => $this->faker->numberBetween(30, 300),
            'is_featured' => $this->faker->boolean(20),
            'created_by' => User::factory()->for($center, 'center'),
            'cloned_from_id' => null,
            'publish_at' => $this->faker->dateTimeBetween('-1 month', '+1 month'),
        ];
    }
}
