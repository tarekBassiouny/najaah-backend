<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AIGenerationStatus;
use App\Models\AIGenerationJob;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AIGenerationJob>
 */
class AIGenerationJobFactory extends Factory
{
    protected $model = AIGenerationJob::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'quiz_id' => Quiz::factory(),
            'source_type' => AIGenerationJob::SOURCE_TYPE_VIDEO,
            'source_id' => fake()->numberBetween(1, 100),
            'status' => AIGenerationStatus::Pending,
            'questions_requested' => fake()->numberBetween(3, 10),
            'questions_generated' => 0,
            'ai_provider' => null,
            'ai_model' => null,
            'prompt_used' => null,
            'generated_questions' => null,
            'error_message' => null,
            'started_at' => null,
            'completed_at' => null,
            'created_by' => User::factory(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => AIGenerationStatus::Pending,
            'started_at' => null,
            'completed_at' => null,
        ]);
    }

    public function processing(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => AIGenerationStatus::Processing,
            'started_at' => now(),
            'completed_at' => null,
        ]);
    }

    public function completed(): static
    {
        $questionsRequested = fake()->numberBetween(3, 10);

        return $this->state(fn (array $attributes): array => [
            'status' => AIGenerationStatus::Completed,
            'questions_requested' => $questionsRequested,
            'questions_generated' => $questionsRequested,
            'ai_provider' => 'openai',
            'ai_model' => 'gpt-4',
            'started_at' => now()->subMinutes(2),
            'completed_at' => now(),
            'generated_questions' => $this->generateSampleQuestions($questionsRequested),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => AIGenerationStatus::Failed,
            'started_at' => now()->subMinutes(1),
            'completed_at' => now(),
            'error_message' => 'API request failed: Rate limit exceeded',
        ]);
    }

    public function fromVideo(?int $videoId = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'source_type' => AIGenerationJob::SOURCE_TYPE_VIDEO,
            'source_id' => $videoId ?? fake()->numberBetween(1, 100),
        ]);
    }

    public function fromPdf(?int $pdfId = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'source_type' => AIGenerationJob::SOURCE_TYPE_PDF,
            'source_id' => $pdfId ?? fake()->numberBetween(1, 100),
        ]);
    }

    public function forQuiz(Quiz $quiz): static
    {
        return $this->state(fn (array $attributes): array => [
            'quiz_id' => $quiz->id,
        ]);
    }

    /**
     * @return array<array<string, mixed>>
     */
    private function generateSampleQuestions(int $count): array
    {
        $questions = [];
        for ($i = 0; $i < $count; $i++) {
            $questions[] = [
                'question' => fake()->sentence().'?',
                'options' => [
                    ['label' => 'A', 'text' => fake()->sentence(3), 'is_correct' => $i % 4 === 0],
                    ['label' => 'B', 'text' => fake()->sentence(3), 'is_correct' => $i % 4 === 1],
                    ['label' => 'C', 'text' => fake()->sentence(3), 'is_correct' => $i % 4 === 2],
                    ['label' => 'D', 'text' => fake()->sentence(3), 'is_correct' => $i % 4 === 3],
                ],
                'explanation' => fake()->sentence(),
                'difficulty' => fake()->randomElement(['easy', 'medium', 'hard']),
            ];
        }

        return $questions;
    }
}
