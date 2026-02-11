<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingSeeder extends Seeder
{
    public function run(): void
    {
        SystemSetting::factory()->create([
            'key' => 'site_name',
            'value' => [
                'en' => 'Najaah LMS',
                'ar' => 'نظام إدارة التعلم Najaah',
            ],
            'is_public' => true,
        ]);

        SystemSetting::factory()->create([
            'key' => 'support_email',
            'value' => ['email' => 'support@example.com'],
            'is_public' => true,
        ]);
    }
}
