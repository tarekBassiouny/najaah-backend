<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'site_name' => [
                'value' => [
                    'en' => 'Najaah LMS',
                    'ar' => 'نظام إدارة التعلم Najaah',
                ],
                'is_public' => true,
            ],
            'support_email' => [
                'value' => ['email' => 'support@example.com'],
                'is_public' => true,
            ],
            'timezone' => [
                'value' => ['timezone' => 'UTC'],
                'is_public' => true,
            ],
            'require_device_approval' => [
                'value' => ['enabled' => false],
                'is_public' => true,
            ],
            'attendance_required' => [
                'value' => ['enabled' => false],
                'is_public' => true,
            ],
            'whatsapp_bulk_settings' => [
                'value' => [
                    'delay_seconds' => 3,
                    'batch_size' => 50,
                    'batch_pause_seconds' => 60,
                    'max_retries' => 2,
                    'max_failures_before_pause' => 10,
                ],
                'is_public' => false,
            ],
            'max_view_limit' => [
                'value' => ['value' => 10],
                'is_public' => true,
            ],
            'max_device_limit' => [
                'value' => ['value' => 3],
                'is_public' => true,
            ],
            'force_disable_extra_view_requests' => [
                'value' => ['enabled' => false],
                'is_public' => true,
            ],
            'force_disable_pdf_download' => [
                'value' => ['enabled' => false],
                'is_public' => true,
            ],
            'force_disable_guest_browsing' => [
                'value' => ['enabled' => false],
                'is_public' => true,
            ],
            'max_web_device_limit' => [
                'value' => ['value' => 3],
                'is_public' => false,
            ],
            'force_disable_web_access' => [
                'value' => ['enabled' => false],
                'is_public' => false,
            ],
            'force_disable_web_playback' => [
                'value' => ['enabled' => false],
                'is_public' => false,
            ],
            'force_disable_parent_portal' => [
                'value' => ['enabled' => false],
                'is_public' => false,
            ],
        ];

        foreach ($settings as $key => $attributes) {
            SystemSetting::query()->updateOrCreate(
                ['key' => $key],
                $attributes,
            );
        }
    }
}
