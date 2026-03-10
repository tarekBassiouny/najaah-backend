<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\QuizAttempt;
use App\Models\QuizAttemptAnswer;
use App\Models\QuizQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuizAttemptAnswer>
 */
class QuizAttemptAnswerFactory extends Factory
{
    protected $model = QuizAttemptAnswer::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'quiz_attempt_id' => QuizAttempt::factory(),
            'quiz_question_id' => QuizQuestion::factory(),
            'selected_answer_ids' => [fake()->numberBetween(1, 4)],
            'is_correct' => fake()->boolean(),
            'points_earned' => fake()->randomFloat(2, 0, 1),
            'answered_at' => now(),
        ];
    }

    public function correct(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_correct' => true,
            'points_earned' => 1.00,
        ]);
    }

    public function incorrect(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_correct' => false,
            'points_earned' => 0.00,
        ]);
    }

    public function forAttempt(QuizAttempt $attempt): static
    {
        return $this->state(fn (array $attributes): array => [
            'quiz_attempt_id' => $attempt->id,
        ]);
    }

    public function forQuestion(QuizQuestion $question): static
    {
        return $this->state(fn (array $attributes): array => [
            'quiz_question_id' => $question->id,
        ]);
    }

    public function withAnswers(array $answerIds): static
    {
        return $this->state(fn (array $attributes): array => [
            'selected_answer_ids' => $answerIds,
        ]);
    }
}
