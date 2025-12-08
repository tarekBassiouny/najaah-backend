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
                'en' => 'Video title',
                'ar' => 'عنوان الفيديو',
            ],

            'description_translations' => [
                'en' => 'Video description',
                'ar' => 'وصف الفيديو',
            ],

            'source_type' => 0,
            'source_provider' => 'bunny',
            'source_id' => Str::uuid()->toString(),
            'source_url' => 'https://example.com/video.mp4',
            'duration_seconds' => 120,
            'lifecycle_status' => 2,
            'tags' => [
                'type' => 'intro',
            ],
            'created_by' => User::factory(),
            'upload_session_id' => VideoUploadSession::factory(),
            'original_filename' => 'video.mp4',
            'encoding_status' => 1,
            'thumbnail_url' => 'https://example.com/thumb.jpg',
            'thumbnail_urls' => [
                'small' => 'https://example.com/thumb-small.jpg',
                'medium' => 'https://example.com/thumb-medium.jpg',
                'large' => 'https://example.com/thumb-large.jpg',
            ],
        ];
    }
}
