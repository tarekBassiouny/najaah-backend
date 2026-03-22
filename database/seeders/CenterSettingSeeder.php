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
                    'branding' => [
                        'logo_url' => $center->logo_url,
                        'primary_color' => $center->primary_color,
                    ],
                    'features' => [
                        'ai_content' => true,
                        'codes_access' => true,
                        'whatsapp_bulk' => true,
                        'guest_browsing' => true,
                        'pdf_downloads' => true,
                    ],
                ],
            ]);
        });
    }
}
