<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_devices', function (Blueprint $table): void {
            $table->string('device_name')->nullable()->after('device_id');
            $table->string('device_type')->nullable()->after('device_name');
        });
    }

    public function down(): void
    {
        Schema::table('user_devices', function (Blueprint $table): void {
            $table->dropColumn(['device_name', 'device_type']);
        });
    }
};
