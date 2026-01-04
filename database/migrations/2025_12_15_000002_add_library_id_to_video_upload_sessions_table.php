<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('video_upload_sessions', function (Blueprint $table): void {
            $table->unsignedBigInteger('library_id')->nullable()->after('uploaded_by');
            $table->dateTime('expires_at')->nullable()->after('progress_percent');
        });
    }

    public function down(): void
    {
        Schema::table('video_upload_sessions', function (Blueprint $table): void {
            $table->dropColumn('library_id');
            $table->dropColumn('expires_at');
        });
    }
};
