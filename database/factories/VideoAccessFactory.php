<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\VideoAccess;
use App\Models\VideoAccessCode;
use Illuminate\Database\Eloquent\Factories\Factory;

class VideoAccessFactory extends Factory
{
    protected $model = VideoAccess::class;

    public function definition(): array
    {
        /** @var VideoAccessCode $code */
        $code = VideoAccessCode::factory()->create();

        return [
            'user_id' => $code->user_id,
            'video_id' => $code->video_id,
            'course_id' => $code->course_id,
            'center_id' => $code->center_id,
            'enrollment_id' => $code->enrollment_id,
            'video_access_request_id' => $code->video_access_request_id,
            'video_access_code_id' => $code->id,
            'granted_at' => now(),
            'revoked_at' => null,
            'revoked_by' => null,
        ];
    }
}
