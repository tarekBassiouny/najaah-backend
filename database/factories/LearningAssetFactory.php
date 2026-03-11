<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\LearningAssetStatus;
use App\Enums\LearningAssetType;
use App\Models\Center;
use App\Models\Course;
use App\Models\LearningAsset;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LearningAsset>
 */
class LearningAssetFactory extends Factory
{
    protected $model = LearningAsset::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'center_id' => Center::factory(),
            'course_id' => Course::factory(),
            'attachable_type' => 'course',
            'attachable_id' => static fn (array $attributes): int => (int) $attributes['course_id'],
            'asset_type' => LearningAssetType::Summary,
            'status' => LearningAssetStatus::Draft,
            'title_translations' => [
                'en' => $this->faker->sentence(4),
                'ar' => $this->faker->sentence(4),
            ],
            'content_translations' => [
                'en' => $this->faker->paragraph(),
                'ar' => $this->faker->paragraph(),
            ],
            'payload' => [
                'content' => $this->faker->paragraph(),
            ],
            'is_active' => false,
            'created_by' => User::factory(),
            'updated_by' => null,
            'published_by' => null,
            'published_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (): array => [
            'status' => LearningAssetStatus::Published,
            'is_active' => true,
            'published_by' => User::factory(),
            'published_at' => now(),
        ]);
    }
}
