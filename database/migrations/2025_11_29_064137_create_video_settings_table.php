<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('video_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('video_id')
                ->constrained('videos')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->json('settings');
            $table->timestamps();
            $table->softDeletes();
            $table->unique('video_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_settings');
    }
};
