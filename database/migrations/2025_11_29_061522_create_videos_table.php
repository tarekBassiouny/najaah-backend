<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('videos', function (Blueprint $table): void {
            $table->id();
            $table->json('title_translations');
            $table->json('description_translations')->nullable();
            $table->tinyInteger('source_type'); // 0=url,1=native
            $table->string('source_provider');
            $table->string('source_id')->nullable();
            $table->string('source_url')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->tinyInteger('lifecycle_status');
            $table->json('tags')->nullable();
            $table->foreignId('created_by')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->unsignedBigInteger('upload_session_id')->nullable();
            $table->string('original_filename')->nullable();
            $table->tinyInteger('encoding_status')->default(0); // 0 pending,1 uploading,2 processing,3 ready
            $table->string('thumbnail_url')->nullable();
            $table->json('thumbnail_urls')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('upload_session_id')
                ->references('id')
                ->on('video_upload_sessions')
                ->cascadeOnUpdate()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('videos');
    }
};
