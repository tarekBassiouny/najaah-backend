<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('center_settings')->orderBy('id')->each(function (object $row): void {
            /** @var array<string, mixed> $settings */
            $settings = json_decode($row->settings, true) ?? [];

            $settings['allow_web_access'] = $settings['allow_web_access'] ?? false;
            $settings['allow_web_playback'] = $settings['allow_web_playback'] ?? false;
            $settings['web_device_limit'] = $settings['web_device_limit'] ?? 1;
            $settings['allow_parent_portal'] = $settings['allow_parent_portal'] ?? false;

            $features = $settings['features'] ?? [];
            $features['web_access'] = $features['web_access'] ?? false;
            $features['web_playback'] = $features['web_playback'] ?? false;
            $features['parent_portal'] = $features['parent_portal'] ?? false;
            $settings['features'] = $features;

            DB::table('center_settings')
                ->where('id', $row->id)
                ->update(['settings' => json_encode($settings)]);
        });
    }

    public function down(): void
    {
        DB::table('center_settings')->orderBy('id')->each(function (object $row): void {
            /** @var array<string, mixed> $settings */
            $settings = json_decode($row->settings, true) ?? [];

            unset(
                $settings['allow_web_access'],
                $settings['allow_web_playback'],
                $settings['web_device_limit'],
                $settings['allow_parent_portal'],
            );

            $features = $settings['features'] ?? [];
            unset($features['web_access'], $features['web_playback'], $features['parent_portal']);
            $settings['features'] = $features;

            DB::table('center_settings')
                ->where('id', $row->id)
                ->update(['settings' => json_encode($settings)]);
        });
    }
};
