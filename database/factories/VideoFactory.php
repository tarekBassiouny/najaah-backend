<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use App\Models\Video;
use App\Models\VideoUploadSession;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class VideoFactory extends Factory
{
    protected $model = Video::class;

    public function definition(): array
    {
        return [
            'title_translations' => [
                'en' => 'Video: '.$this->faker->sentence(),
                'ar' => 'فيديو: '.$this->faker->sentence(),
            ],

            'description_translations' => [
                'en' => $this->faker->paragraph(),
                'ar' => 'وصف: '.$this->faker->sentence(),
            ],

            'source_type' => $this->faker->numberBetween(0, 1),
            'source_provider' => $this->faker->randomElement(['bunny', 'youtube', 'vimeo', 'zoom', 'custom']),
            'source_id' => Str::uuid()->toString(),
            'source_url' => $this->faker->url(),
            'duration_seconds' => $this->faker->numberBetween(30, 3600),
            'lifecycle_status' => $this->faker->numberBetween(0, 4),
            'tags' => [
                'type' => $this->faker->randomElement(['intro', 'part', 'qna']),
            ],
            'created_by' => User::factory(),
            'upload_session_id' => VideoUploadSession::factory(),
            'original_filename' => $this->faker->word().'.mp4',
            'encoding_status' => $this->faker->numberBetween(0, 3),
            'thumbnail_url' => $this->faker->imageUrl(),
            'thumbnail_urls' => [
                'small' => $this->faker->imageUrl(),
                'medium' => $this->faker->imageUrl(),
                'large' => $this->faker->imageUrl(),
            ],
        ];
    }
}
