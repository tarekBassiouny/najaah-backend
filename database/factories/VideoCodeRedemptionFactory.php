<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use App\Models\VideoAccess;
use App\Models\VideoCodeBatch;
use App\Models\VideoCodeRedemption;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VideoCodeRedemption>
 */
class VideoCodeRedemptionFactory extends Factory
{
    protected $model = VideoCodeRedemption::class;

    public function definition(): array
    {
        /** @var VideoCodeBatch $batch */
        $batch = VideoCodeBatch::factory()->create();

        /** @var User $student */
        $student = User::factory()->create([
            'is_student' => true,
            'center_id' => $batch->center_id,
        ]);

        /** @var VideoAccess $access */
        $access = VideoAccess::factory()->create([
            'user_id' => $student->id,
            'video_id' => $batch->video_id,
            'course_id' => $batch->course_id,
            'center_id' => $batch->center_id,
            'total_view_limit' => $batch->view_limit_per_code,
        ]);

        return [
            'batch_id' => $batch->id,
            'sequence_number' => $this->faker->numberBetween(1, $batch->quantity),
            'user_id' => $student->id,
            'video_access_id' => $access->id,
            'redeemed_at' => now(),
        ];
    }

    public function forBatch(VideoCodeBatch $batch): static
    {
        return $this->state(fn (array $attributes) => [
            'batch_id' => $batch->id,
        ]);
    }

    public function withSequence(int $sequence): static
    {
        return $this->state(fn (array $attributes) => [
            'sequence_number' => $sequence,
        ]);
    }
}
