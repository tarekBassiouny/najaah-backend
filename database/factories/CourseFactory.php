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
                'en' => 'Sample Course',
                'ar' => 'دورة تجريبية',
            ],
            'description_translations' => [
                'en' => 'Course description',
                'ar' => 'وصف الدورة',
            ],
            'college_translations' => [
                'en' => 'Sample College',
                'ar' => 'كلية تجريبية',
            ],
            'grade_year' => '1',
            'thumbnail_url' => 'https://via.placeholder.com/640x360.png',
            'difficulty_level' => 1,
            'language' => 'en',
            'course_code' => 'COURSE-'.uniqid(),
            'primary_instructor_id' => null,
            'tags' => [
                'module' => 'module',
                'type' => 'intro',
            ],
            'status' => 0,
            'is_published' => false,
            'duration_minutes' => 60,
            'is_featured' => false,
            'created_by' => User::factory()->for($center, 'center'),
            'cloned_from_id' => null,
            'publish_at' => now(),
        ];
    }
}
