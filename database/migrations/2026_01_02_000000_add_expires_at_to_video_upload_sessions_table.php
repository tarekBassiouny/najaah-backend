<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('video_upload_sessions', 'expires_at')) {
            Schema::table('video_upload_sessions', function (Blueprint $table): void {
                $table->dateTime('expires_at')->nullable()->after('progress_percent');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('video_upload_sessions', 'expires_at')) {
            Schema::table('video_upload_sessions', function (Blueprint $table): void {
                $table->dropColumn('expires_at');
            });
        }
    }
};
