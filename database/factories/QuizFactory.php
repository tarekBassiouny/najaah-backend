<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AttemptScorePolicy;
use App\Models\Center;
use App\Models\Course;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Quiz>
 */
class QuizFactory extends Factory
{
    protected $model = Quiz::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $center = Center::factory();
        $course = Course::factory()->for($center, 'center');

        return [
            'center_id' => $center,
            'course_id' => $course,
            'title_translations' => [
                'en' => fake()->sentence(3),
                'ar' => 'اختبار '.fake()->word(),
            ],
            'description_translations' => [
                'en' => fake()->paragraph(),
                'ar' => 'وصف الاختبار',
            ],
            'attachable_type' => 'course',
            'attachable_id' => null,
            'passing_score' => fake()->randomFloat(2, 50, 80),
            'max_attempts' => fake()->randomElement([0, 1, 3, 5]),
            'attempt_score_policy' => fake()->randomElement(AttemptScorePolicy::cases()),
            'time_limit_minutes' => fake()->randomElement([null, 15, 30, 60]),
            'shuffle_questions' => fake()->boolean(50),
            'shuffle_answers' => fake()->boolean(50),
            'show_correct_answers' => fake()->boolean(70),
            'show_score_immediately' => true,
            'is_required' => fake()->boolean(30),
            'is_active' => true,
            'available_from' => null,
            'available_until' => null,
            'order_index' => fake()->numberBetween(0, 10),
            'created_by' => User::factory()->for($center, 'center'),
        ];
    }

    public function forCourse(Course $course): static
    {
        return $this->state(fn (array $attributes): array => [
            'center_id' => $course->center_id,
            'course_id' => $course->id,
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    public function required(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_required' => true,
        ]);
    }

    public function timed(int $minutes = 30): static
    {
        return $this->state(fn (array $attributes): array => [
            'time_limit_minutes' => $minutes,
        ]);
    }

    public function unlimited(): static
    {
        return $this->state(fn (array $attributes): array => [
            'max_attempts' => 0,
        ]);
    }

    public function singleAttempt(): static
    {
        return $this->state(fn (array $attributes): array => [
            'max_attempts' => 1,
        ]);
    }

    public function bestScore(): static
    {
        return $this->state(fn (array $attributes): array => [
            'attempt_score_policy' => AttemptScorePolicy::Best,
        ]);
    }

    public function latestScore(): static
    {
        return $this->state(fn (array $attributes): array => [
            'attempt_score_policy' => AttemptScorePolicy::Latest,
        ]);
    }

    public function averageScore(): static
    {
        return $this->state(fn (array $attributes): array => [
            'attempt_score_policy' => AttemptScorePolicy::Average,
        ]);
    }
}
