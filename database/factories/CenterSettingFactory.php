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
                'device_limit' => 1,
                'allow_guest_browsing' => false,
                'education_profile' => [
                    'enable_grade' => true,
                    'enable_school' => true,
                    'enable_college' => true,
                    'enable_parent_phone' => true,
                    'require_grade' => false,
                    'require_school' => false,
                    'require_college' => false,
                    'require_parent_phone' => false,
                ],
                'features' => [
                    'ai_content' => true,
                    'codes_access' => true,
                    'whatsapp_bulk' => true,
                    'guest_browsing' => true,
                    'pdf_downloads' => true,
                ],
            ],
        ];
    }
}
