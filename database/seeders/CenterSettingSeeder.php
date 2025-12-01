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
                    'pdf_download_permission' => false,
                    'device_limit' => 1,
                    'branding' => [
                        'logo_url' => $center->logo_url,
                        'primary_color' => $center->primary_color,
                    ],
                ],
            ]);
        });
    }
}
