<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\QuizAnswer;
use App\Models\QuizQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuizAnswer>
 */
class QuizAnswerFactory extends Factory
{
    protected $model = QuizAnswer::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'quiz_question_id' => QuizQuestion::factory(),
            'answer_translations' => [
                'en' => fake()->sentence(3),
                'ar' => 'إجابة '.fake()->word(),
            ],
            'is_correct' => false,
            'order_index' => fake()->numberBetween(0, 4),
        ];
    }

    public function correct(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_correct' => true,
        ]);
    }

    public function incorrect(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_correct' => false,
        ]);
    }
}
