<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Center;
use App\Models\User;
use App\Models\VideoUploadSession;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class VideoUploadSessionFactory extends Factory
{
    protected $model = VideoUploadSession::class;

    public function definition(): array
    {
        return [
            'center_id' => Center::factory(),
            'uploaded_by' => User::factory(),
            'bunny_upload_id' => Str::uuid()->toString(),
            'upload_status' => $this->faker->numberBetween(0, 5),
            'progress_percent' => $this->faker->numberBetween(0, 100),
            'error_message' => null,
        ];
    }
}
