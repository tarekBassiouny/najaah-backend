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
            $table->foreignId('course_id')
                ->nullable()
                ->after('video_id')
                ->constrained('courses')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->foreignId('enrollment_id')
                ->nullable()
                ->after('course_id')
                ->constrained('enrollments')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->text('embed_token')->nullable()->after('enrollment_id');
            $table->timestamp('embed_token_expires_at')->nullable()->after('embed_token');

            $table->index('embed_token_expires_at');
            $table->index(['course_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::table('playback_sessions', function (Blueprint $table): void {
            $table->dropForeign(['course_id']);
            $table->dropForeign(['enrollment_id']);
            $table->dropIndex(['embed_token_expires_at']);
            $table->dropIndex(['course_id', 'user_id']);
            $table->dropColumn(['course_id', 'enrollment_id', 'embed_token', 'embed_token_expires_at']);
        });
    }
};
