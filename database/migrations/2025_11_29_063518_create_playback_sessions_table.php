<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('playback_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('video_id')
                ->constrained('videos')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('device_id')
                ->constrained('user_devices')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->unsignedInteger('progress_percent')->default(0);
            $table->boolean('is_full_play')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['user_id', 'video_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('playback_sessions');
    }
};
