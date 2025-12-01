<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Video;
use App\Models\VideoSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

class VideoSettingFactory extends Factory
{
    protected $model = VideoSetting::class;

    public function definition(): array
    {
        return [
            'video_id' => Video::factory(),
            'settings' => [
                'view_limit' => 2,
                'allow_extra_view_requests' => true,
            ],
        ];
    }
}
