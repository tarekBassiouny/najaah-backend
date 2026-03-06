<?php

namespace Database\Seeders;

use App\Models\Center;
use App\Models\CenterSetting;
use Illuminate\Database\Seeder;

class CenterSettingSeeder extends Seeder
{
    public function run(): void
    {
        Center::all()->each(function (Center $center): void {
            CenterSetting::factory()->create([
                'center_id' => $center->id,
                'settings' => [
                    'default_view_limit' => 2,
                    'allow_extra_view_requests' => true,
                    'requires_video_approval' => false,
                    'video_code_expiry_days' => null,
                    'pdf_download_permission' => false,
                    'device_limit' => 1,
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
                    'branding' => [
                        'logo_url' => $center->logo_url,
                        'primary_color' => $center->primary_color,
                    ],
                ],
            ]);
        });
    }
}
