<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('playback_sessions', function (Blueprint $table): void {
            $table->timestamp('last_activity_at')->nullable()->after('expires_at');
            $table->boolean('auto_closed')->default(false)->after('is_full_play');
            $table->boolean('is_locked')->default(false)->after('auto_closed');
            $table->unsignedInteger('watch_duration')->default(0)->after('is_locked');
            $table->string('close_reason', 20)->nullable()->after('watch_duration');

            $table->index(['ended_at', 'last_activity_at'], 'playback_sessions_cleanup_index');
        });
    }

    public function down(): void
    {
        Schema::table('playback_sessions', function (Blueprint $table): void {
            $table->dropIndex('playback_sessions_cleanup_index');

            $table->dropColumn([
                'last_activity_at',
                'auto_closed',
                'is_locked',
                'watch_duration',
                'close_reason',
            ]);
        });
    }
};
