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

        SystemSetting::factory()->create([
            'key' => 'timezone',
            'value' => ['timezone' => 'UTC'],
            'is_public' => true,
        ]);

        SystemSetting::factory()->create([
            'key' => 'require_device_approval',
            'value' => ['enabled' => false],
            'is_public' => true,
        ]);

        SystemSetting::factory()->create([
            'key' => 'attendance_required',
            'value' => ['enabled' => false],
            'is_public' => true,
        ]);

        SystemSetting::factory()->create([
            'key' => 'whatsapp_bulk_settings',
            'value' => [
                'delay_seconds' => 3,
                'batch_size' => 50,
                'batch_pause_seconds' => 60,
                'max_retries' => 2,
                'max_failures_before_pause' => 10,
            ],
            'is_public' => false,
        ]);

        SystemSetting::factory()->create([
            'key' => 'max_view_limit',
            'value' => ['value' => 10],
            'is_public' => true,
        ]);

        SystemSetting::factory()->create([
            'key' => 'max_device_limit',
            'value' => ['value' => 3],
            'is_public' => true,
        ]);

        SystemSetting::factory()->create([
            'key' => 'force_disable_extra_view_requests',
            'value' => ['enabled' => false],
            'is_public' => true,
        ]);

        SystemSetting::factory()->create([
            'key' => 'force_disable_pdf_download',
            'value' => ['enabled' => false],
            'is_public' => true,
        ]);

        SystemSetting::factory()->create([
            'key' => 'force_disable_guest_browsing',
            'value' => ['enabled' => false],
            'is_public' => true,
        ]);
    }
}
