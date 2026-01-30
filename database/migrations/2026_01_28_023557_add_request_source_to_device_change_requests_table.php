<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('device_change_requests', function (Blueprint $table): void {
            $table->string('request_source', 20)->default('MOBILE')->after('status');
            $table->timestamp('otp_verified_at')->nullable()->after('request_source');
        });

        Schema::table('device_change_requests', function (Blueprint $table): void {
            $table->string('current_device_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('device_change_requests', function (Blueprint $table): void {
            $table->dropColumn(['request_source', 'otp_verified_at']);
        });

        Schema::table('device_change_requests', function (Blueprint $table): void {
            $table->string('current_device_id')->nullable(false)->change();
        });
    }
};
