<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('center_settings')
            ->whereNull('center_id')
            ->delete();

        DB::table('center_settings')
            ->whereNull('settings')
            ->delete();

        $centers = DB::table('centers')
            ->select([
                'id',
                'default_view_limit',
                'allow_extra_view_requests',
                'pdf_download_permission',
                'device_limit',
                'logo_url',
                'primary_color',
            ])
            ->get();

        foreach ($centers as $center) {
            $branding = array_filter([
                'logo_url' => $center->logo_url,
                'primary_color' => $center->primary_color,
            ], static fn ($value): bool => $value !== null);

            DB::table('center_settings')->updateOrInsert(
                ['center_id' => $center->id],
                [
                    'settings' => json_encode([
                        'default_view_limit' => (int) $center->default_view_limit,
                        'allow_extra_view_requests' => (bool) $center->allow_extra_view_requests,
                        'pdf_download_permission' => (bool) $center->pdf_download_permission,
                        'device_limit' => (int) $center->device_limit,
                        'branding' => $branding,
                    ], JSON_THROW_ON_ERROR),
                    'updated_at' => now(),
                    'created_at' => now(),
                    'deleted_at' => null,
                ],
            );
        }

        $defaults = [
            'support_email' => ['email' => 'support@example.com'],
            'timezone' => ['timezone' => 'UTC'],
            'require_device_approval' => ['enabled' => false],
            'attendance_required' => ['enabled' => false],
        ];

        foreach ($defaults as $key => $value) {
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
    }

    public function down(): void
    {
        DB::table('system_settings')
            ->whereIn('key', ['support_email', 'timezone', 'require_device_approval', 'attendance_required'])
            ->delete();
    }
};
