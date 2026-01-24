<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\VideoUploadStatus;
use App\Models\Center;
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
        $center = Center::factory();

        return [
            'center_id' => $center,
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
            'created_by' => User::factory()->for($center, 'center'),
            'library_id' => (int) (config('bunny.api.library_id') ?? 1),
            'upload_session_id' => VideoUploadSession::factory(),
            'original_filename' => 'video.mp4',
            'encoding_status' => VideoUploadStatus::Uploading,
            'thumbnail_url' => 'https://example.com/thumb.jpg',
            'thumbnail_urls' => [
                'small' => 'https://example.com/thumb-small.jpg',
                'medium' => 'https://example.com/thumb-medium.jpg',
                'large' => 'https://example.com/thumb-large.jpg',
            ],
        ];
    }
}
