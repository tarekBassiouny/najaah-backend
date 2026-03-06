<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\VideoAccessCodeStatus;
use App\Models\User;
use App\Models\VideoAccessCode;
use App\Models\VideoAccessRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

class VideoAccessCodeFactory extends Factory
{
    protected $model = VideoAccessCode::class;

    public function definition(): array
    {
        /** @var VideoAccessRequest $request */
        $request = VideoAccessRequest::factory()->create();

        return [
            'user_id' => $request->user_id,
            'video_id' => $request->video_id,
            'course_id' => $request->course_id,
            'center_id' => $request->center_id,
            'enrollment_id' => $request->enrollment_id,
            'video_access_request_id' => $request->id,
            'code' => strtoupper($this->faker->bothify('??##??##')),
            'status' => VideoAccessCodeStatus::Active,
            'generated_by' => User::factory()->create([
                'is_student' => false,
                'center_id' => $request->center_id,
            ])->id,
            'generated_at' => now(),
            'used_at' => null,
            'expires_at' => now()->addDays(30),
            'revoked_at' => null,
            'revoked_by' => null,
        ];
    }
}
