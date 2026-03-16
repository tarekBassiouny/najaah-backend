<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CourseAccessModel;
use App\Enums\VideoCodeBatchStatus;
use App\Models\Center;
use App\Models\Course;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoCodeBatch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VideoCodeBatch>
 */
class VideoCodeBatchFactory extends Factory
{
    private const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    protected $model = VideoCodeBatch::class;

    public function definition(): array
    {
        /** @var Center $center */
        $center = Center::factory()->create();

        /** @var Course $course */
        $course = Course::factory()->create([
            'center_id' => $center->id,
            'access_model' => CourseAccessModel::VideoCode,
        ]);

        /** @var Video $video */
        $video = Video::factory()->create([
            'center_id' => $center->id,
        ]);

        // Attach video to course
        $course->videos()->attach($video->id, [
            'section_id' => null,
            'order_index' => 0,
            'visible' => true,
        ]);

        /** @var User $admin */
        $admin = User::factory()->create([
            'is_student' => false,
            'center_id' => $center->id,
        ]);

        return [
            'video_id' => $video->id,
            'course_id' => $course->id,
            'center_id' => $center->id,
            'batch_code' => $this->randomBatchCode(),
            'secret_key' => bin2hex(random_bytes(32)),
            'quantity' => $this->faker->numberBetween(10, 100),
            'sold_limit' => null,
            'redeemed_count' => 0,
            'view_limit_per_code' => $this->faker->numberBetween(1, 5),
            'status' => VideoCodeBatchStatus::Open,
            'generated_by' => $admin->id,
            'generated_at' => now(),
            'closed_at' => null,
            'closed_by' => null,
            'metadata' => ['exports' => []],
        ];
    }

    public function closed(int $soldLimit = 50): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => VideoCodeBatchStatus::Closed,
            'sold_limit' => $soldLimit,
            'closed_at' => now(),
            'closed_by' => $attributes['generated_by'],
        ]);
    }

    public function withQuantity(int $quantity): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => $quantity,
        ]);
    }

    public function withViewLimit(int $viewLimit): static
    {
        return $this->state(fn (array $attributes) => [
            'view_limit_per_code' => $viewLimit,
        ]);
    }

    private function randomBatchCode(): string
    {
        $code = '';
        $maxIndex = strlen(self::ALPHABET) - 1;

        for ($index = 0; $index < 4; $index++) {
            $code .= self::ALPHABET[random_int(0, $maxIndex)];
        }

        return $code;
    }
}
