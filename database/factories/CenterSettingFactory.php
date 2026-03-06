<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Center;
use App\Models\CenterSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

class CenterSettingFactory extends Factory
{
    protected $model = CenterSetting::class;

    public function definition(): array
    {
        return [
            'center_id' => Center::factory(),
            'settings' => [
                'default_view_limit' => 2,
                'allow_extra_view_requests' => true,
                'requires_video_approval' => false,
                'video_code_expiry_days' => null,
                'pdf_download_permission' => false,
                'education_profile' => [
                    'enable_grade' => true,
                    'enable_school' => true,
                    'enable_college' => true,
                    'require_grade' => false,
                    'require_school' => false,
                    'require_college' => false,
                ],
                'whatsapp_bulk_settings' => [
                    'delay_seconds' => 3,
                    'batch_size' => 50,
                    'batch_pause_seconds' => 60,
                    'max_retries' => 2,
                    'max_failures_before_pause' => 10,
                ],
            ],
        ];
    }
}
