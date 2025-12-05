<?php

declare(strict_types=1);

namespace Database\Factories\Pivots;

use App\Models\Course;
use App\Models\Instructor;
use App\Models\Pivots\CourseInstructor;
use Illuminate\Database\Eloquent\Factories\Factory;

class CourseInstructorFactory extends Factory
{
    protected $model = CourseInstructor::class;

    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'instructor_id' => Instructor::factory(),
            'role' => $this->faker->randomElement(['lead', 'assistant', 'guest']),
        ];
    }
}
