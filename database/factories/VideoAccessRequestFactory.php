<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\EnrollmentStatus;
use App\Enums\VideoAccessRequestStatus;
use App\Models\Center;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoAccessRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

class VideoAccessRequestFactory extends Factory
{
    protected $model = VideoAccessRequest::class;

    public function definition(): array
    {
        $center = Center::factory()->create();
        $course = Course::factory()->create([
            'center_id' => $center->id,
            'status' => 3,
            'is_published' => true,
        ]);

        $video = Video::factory()->create([
            'center_id' => $center->id,
        ]);

        $course->videos()->attach($video->id, [
            'section_id' => null,
            'order_index' => 1,
            'visible' => true,
            'view_limit_override' => null,
        ]);

        $student = User::factory()->create([
            'is_student' => true,
            'center_id' => $center->id,
        ]);

        $enrollment = Enrollment::factory()->create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'center_id' => $center->id,
            'status' => EnrollmentStatus::Active,
        ]);

        return [
            'user_id' => $student->id,
            'video_id' => $video->id,
            'course_id' => $course->id,
            'center_id' => $center->id,
            'enrollment_id' => $enrollment->id,
            'status' => VideoAccessRequestStatus::Pending,
            'reason' => $this->faker->sentence(),
            'decision_reason' => null,
            'decided_by' => null,
            'decided_at' => null,
        ];
    }
}
