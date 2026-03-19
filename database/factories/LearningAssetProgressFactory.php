<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\LearningAssetProgressStatus;
use App\Models\Center;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\LearningAsset;
use App\Models\LearningAssetProgress;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LearningAssetProgress>
 */
class LearningAssetProgressFactory extends Factory
{
    protected $model = LearningAssetProgress::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'center_id' => Center::factory(),
            'course_id' => Course::factory(),
            'learning_asset_id' => LearningAsset::factory(),
            'user_id' => User::factory(),
            'enrollment_id' => Enrollment::factory(),
            'status' => LearningAssetProgressStatus::InProgress,
            'progress_percent' => 25,
            'state' => [
                'current_index' => 1,
            ],
            'started_at' => now()->subMinutes(5),
            'last_interacted_at' => now()->subMinute(),
            'completed_at' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (): array => [
            'status' => LearningAssetProgressStatus::Completed,
            'progress_percent' => 100,
            'completed_at' => now(),
        ]);
    }
}
