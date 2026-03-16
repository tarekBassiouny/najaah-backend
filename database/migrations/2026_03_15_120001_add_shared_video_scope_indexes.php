<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('video_code_batches', function (Blueprint $table): void {
            $table->index(
                ['course_id', 'video_id', 'status', 'deleted_at'],
                'video_code_batches_course_video_status_deleted_index'
            );
        });

        Schema::table('playback_sessions', function (Blueprint $table): void {
            $table->index(
                ['user_id', 'video_id', 'course_id', 'is_full_play'],
                'playback_sessions_user_video_course_full_play_index'
            );
        });

        Schema::table('extra_view_requests', function (Blueprint $table): void {
            $table->index(
                ['user_id', 'video_id', 'course_id', 'status'],
                'extra_view_requests_user_video_course_status_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('extra_view_requests', function (Blueprint $table): void {
            $table->dropIndex('extra_view_requests_user_video_course_status_index');
        });

        Schema::table('playback_sessions', function (Blueprint $table): void {
            $table->dropIndex('playback_sessions_user_video_course_full_play_index');
        });

        Schema::table('video_code_batches', function (Blueprint $table): void {
            $table->dropIndex('video_code_batches_course_video_status_deleted_index');
        });
    }
};
