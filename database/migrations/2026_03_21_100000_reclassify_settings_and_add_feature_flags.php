<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Seed new system settings
        $newSystemSettings = [
            'max_view_limit' => ['value' => 10],
            'max_device_limit' => ['value' => 3],
            'force_disable_extra_view_requests' => ['enabled' => false],
            'force_disable_pdf_download' => ['enabled' => false],
            'force_disable_guest_browsing' => ['enabled' => false],
        ];

        foreach ($newSystemSettings as $key => $value) {
            DB::table('system_settings')->updateOrInsert(
                ['key' => $key],
                [
                    'value' => json_encode($value, JSON_THROW_ON_ERROR),
                    'is_public' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                    'deleted_at' => null,
                ],
            );
        }

        // 2. Migrate whatsapp_bulk_settings from center → system default
        $firstCenterWhatsapp = DB::table('center_settings')
            ->whereNull('deleted_at')
            ->whereNotNull('settings')
            ->value('settings');

        $whatsappDefaults = [
            'delay_seconds' => 3,
            'batch_size' => 50,
            'batch_pause_seconds' => 60,
            'max_retries' => 2,
            'max_failures_before_pause' => 10,
        ];

        if (is_string($firstCenterWhatsapp)) {
            $decoded = json_decode($firstCenterWhatsapp, true);
            if (is_array($decoded) && isset($decoded['whatsapp_bulk_settings']) && is_array($decoded['whatsapp_bulk_settings'])) {
                $whatsappDefaults = array_merge($whatsappDefaults, $decoded['whatsapp_bulk_settings']);
            }
        }

        DB::table('system_settings')->updateOrInsert(
            ['key' => 'whatsapp_bulk_settings'],
            [
                'value' => json_encode($whatsappDefaults, JSON_THROW_ON_ERROR),
                'is_public' => false,
                'updated_at' => now(),
                'created_at' => now(),
                'deleted_at' => null,
            ],
        );

        // 3. Remove whatsapp_bulk_settings from center_settings, add feature flags,
        //    and sync allow_guest_browsing from centers column into settings JSON
        $defaultFeatures = [
            'ai_content' => true,
            'codes_access' => true,
            'whatsapp_bulk' => true,
            'guest_browsing' => true,
            'pdf_downloads' => true,
        ];

        $centerSettings = DB::table('center_settings')
            ->whereNull('deleted_at')
            ->get(['id', 'center_id', 'settings']);

        // Build a map of center_id => allow_guest_browsing
        $centerIds = $centerSettings->pluck('center_id')->filter()->all();
        $guestBrowsingMap = [];
        if ($centerIds !== []) {
            $centers = DB::table('centers')
                ->whereIn('id', $centerIds)
                ->get(['id', 'allow_guest_browsing']);
            foreach ($centers as $c) {
                $guestBrowsingMap[(int) $c->id] = (bool) $c->allow_guest_browsing;
            }
        }

        foreach ($centerSettings as $row) {
            $settings = is_string($row->settings) ? json_decode($row->settings, true) : [];
            if (! is_array($settings)) {
                $settings = [];
            }

            unset($settings['whatsapp_bulk_settings']);
            $settings['features'] = $defaultFeatures;

            // Sync allow_guest_browsing from center column if not already in settings
            if (! array_key_exists('allow_guest_browsing', $settings) && $row->center_id !== null) {
                $settings['allow_guest_browsing'] = $guestBrowsingMap[(int) $row->center_id] ?? false;
            }

            DB::table('center_settings')
                ->where('id', $row->id)
                ->update([
                    'settings' => json_encode($settings, JSON_THROW_ON_ERROR),
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        // Remove new system settings
        DB::table('system_settings')
            ->whereIn('key', [
                'max_view_limit',
                'max_device_limit',
                'force_disable_extra_view_requests',
                'force_disable_pdf_download',
                'force_disable_guest_browsing',
                'whatsapp_bulk_settings',
            ])
            ->delete();

        // Remove features from center_settings and restore whatsapp_bulk_settings defaults
        $centerSettings = DB::table('center_settings')
            ->whereNull('deleted_at')
            ->get(['id', 'settings']);

        foreach ($centerSettings as $row) {
            $settings = is_string($row->settings) ? json_decode($row->settings, true) : [];
            if (! is_array($settings)) {
                $settings = [];
            }

            unset($settings['features']);
            $settings['whatsapp_bulk_settings'] = [
                'delay_seconds' => 3,
                'batch_size' => 50,
                'batch_pause_seconds' => 60,
                'max_retries' => 2,
                'max_failures_before_pause' => 10,
            ];

            DB::table('center_settings')
                ->where('id', $row->id)
                ->update([
                    'settings' => json_encode($settings, JSON_THROW_ON_ERROR),
                    'updated_at' => now(),
                ]);
        }
    }
};
