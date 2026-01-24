<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\VideoUploadStatus;
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
        $statuses = VideoUploadStatus::cases();

        return [
            'center_id' => Center::factory(),
            'uploaded_by' => User::factory(),
            'library_id' => (int) (config('bunny.api.library_id') ?? 1),
            'bunny_upload_id' => Str::uuid()->toString(),
            'upload_status' => $this->faker->randomElement($statuses),
            'progress_percent' => $this->faker->numberBetween(0, 100),
            'error_message' => null,
        ];
    }
}
