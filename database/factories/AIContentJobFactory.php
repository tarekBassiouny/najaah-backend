<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AIContentJobStatus;
use App\Enums\AIContentSourceType;
use App\Enums\AIContentTargetType;
use App\Models\AIContentJob;
use App\Models\Center;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AIContentJob>
 */
class AIContentJobFactory extends Factory
{
    protected $model = AIContentJob::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'center_id' => Center::factory(),
            'course_id' => Course::factory(),
            'batch_key' => null,
            'source_type' => AIContentSourceType::Course,
            'source_id' => static fn (array $attributes): int => (int) $attributes['course_id'],
            'target_type' => AIContentTargetType::Summary,
            'target_id' => null,
            'language' => 'ar',
            'status' => AIContentJobStatus::Pending,
            'generation_config' => [],
            'generated_payload' => null,
            'reviewed_payload' => null,
            'validation_warnings' => null,
            'created_by' => User::factory(),
            'reviewed_by' => null,
            'published_by' => null,
            'started_at' => null,
            'completed_at' => null,
            'published_at' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (): array => [
            'status' => AIContentJobStatus::Completed,
            'started_at' => now()->subMinutes(2),
            'completed_at' => now(),
            'generated_payload' => [
                'title' => 'Generated summary',
                'content' => 'Generated content',
            ],
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (): array => [
            'status' => AIContentJobStatus::Approved,
            'reviewed_by' => User::factory(),
            'reviewed_payload' => [
                'title' => 'Reviewed summary',
                'content' => 'Reviewed content',
            ],
        ]);
    }

    public function published(): static
    {
        return $this->state(fn (): array => [
            'status' => AIContentJobStatus::Published,
            'published_by' => User::factory(),
            'published_at' => now(),
        ]);
    }
}
