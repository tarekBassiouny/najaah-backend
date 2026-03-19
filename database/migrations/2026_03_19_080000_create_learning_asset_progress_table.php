<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_asset_progress', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('center_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('course_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('learning_asset_id')
                ->constrained('learning_assets')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('enrollment_id')
                ->nullable()
                ->constrained('enrollments')
                ->nullOnDelete()
                ->cascadeOnUpdate();
            $table->unsignedTinyInteger('status')->default(0);
            $table->unsignedInteger('progress_percent')->default(0);
            $table->json('state')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('last_interacted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['learning_asset_id', 'user_id']);
            $table->index(['user_id', 'course_id']);
            $table->index(['center_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_asset_progress');
    }
};
