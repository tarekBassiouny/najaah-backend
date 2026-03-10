<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\QuizAttemptStatus;
use App\Models\Center;
use App\Models\Enrollment;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuizAttempt>
 */
class QuizAttemptFactory extends Factory
{
    protected $model = QuizAttempt::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $center = Center::factory();
        $quiz = Quiz::factory()->state(['center_id' => $center]);

        return [
            'quiz_id' => $quiz,
            'user_id' => User::factory()->student()->for($center, 'center'),
            'enrollment_id' => Enrollment::factory(),
            'center_id' => $center,
            'attempt_number' => 1,
            'status' => QuizAttemptStatus::InProgress,
            'started_at' => now(),
            'submitted_at' => null,
            'time_spent_seconds' => 0,
            'score' => null,
            'points_earned' => 0,
            'points_possible' => 10,
            'passed' => false,
        ];
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => QuizAttemptStatus::InProgress,
            'submitted_at' => null,
            'score' => null,
        ]);
    }

    public function submitted(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => QuizAttemptStatus::Submitted,
            'submitted_at' => now(),
        ]);
    }

    public function graded(): static
    {
        $score = fake()->randomFloat(2, 0, 100);

        return $this->state(fn (array $attributes): array => [
            'status' => QuizAttemptStatus::Graded,
            'submitted_at' => now()->subMinutes(5),
            'time_spent_seconds' => fake()->numberBetween(300, 1800),
            'score' => $score,
            'points_earned' => $score / 10,
            'passed' => $score >= 70,
        ]);
    }

    public function timedOut(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => QuizAttemptStatus::TimedOut,
            'submitted_at' => now(),
        ]);
    }

    public function passed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => QuizAttemptStatus::Graded,
            'submitted_at' => now()->subMinutes(5),
            'score' => fake()->randomFloat(2, 70, 100),
            'passed' => true,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => QuizAttemptStatus::Graded,
            'submitted_at' => now()->subMinutes(5),
            'score' => fake()->randomFloat(2, 0, 69),
            'passed' => false,
        ]);
    }

    public function forQuiz(Quiz $quiz): static
    {
        return $this->state(fn (array $attributes): array => [
            'quiz_id' => $quiz->id,
            'center_id' => $quiz->center_id,
        ]);
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'user_id' => $user->id,
        ]);
    }

    public function attemptNumber(int $number): static
    {
        return $this->state(fn (array $attributes): array => [
            'attempt_number' => $number,
        ]);
    }
}
