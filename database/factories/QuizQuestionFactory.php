<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\QuestionType;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuizQuestion>
 */
class QuizQuestionFactory extends Factory
{
    protected $model = QuizQuestion::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'quiz_id' => Quiz::factory(),
            'question_translations' => [
                'en' => fake()->sentence().'?',
                'ar' => 'سؤال '.fake()->word().'؟',
            ],
            'question_type' => QuestionType::SingleChoice,
            'explanation_translations' => [
                'en' => fake()->sentence(),
                'ar' => 'شرح الإجابة',
            ],
            'points' => fake()->randomFloat(2, 1, 5),
            'order_index' => fake()->numberBetween(0, 20),
            'is_active' => true,
            'ai_generated' => false,
            'ai_source_type' => null,
            'ai_source_id' => null,
        ];
    }

    public function singleChoice(): static
    {
        return $this->state(fn (array $attributes): array => [
            'question_type' => QuestionType::SingleChoice,
        ]);
    }

    public function multipleChoice(): static
    {
        return $this->state(fn (array $attributes): array => [
            'question_type' => QuestionType::MultipleChoice,
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

    public function aiGenerated(): static
    {
        return $this->state(fn (array $attributes): array => [
            'ai_generated' => true,
            'ai_source_type' => 'video',
            'ai_source_id' => fake()->numberBetween(1, 100),
        ]);
    }

    public function withPoints(float $points): static
    {
        return $this->state(fn (array $attributes): array => [
            'points' => $points,
        ]);
    }
}
