<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Center;
use App\Models\Course;
use App\Models\ExtraViewRequest;
use App\Models\User;
use App\Models\Video;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExtraViewRequestFactory extends Factory
{
    protected $model = ExtraViewRequest::class;

    public function definition(): array
    {
        $course = Course::factory()->create();
        $video = Video::factory()->create();

        $centerId = $course->center_id ?? Center::factory()->create()->id;

        return [
            'user_id' => User::factory(),
            'video_id' => $video->id,
            'course_id' => $course->id,
            'center_id' => $centerId,
            'status' => ExtraViewRequest::STATUS_PENDING,
            'reason' => $this->faker->sentence(),
            'granted_views' => null,
            'decision_reason' => null,
            'decided_by' => null,
            'decided_at' => null,
        ];
    }
}
