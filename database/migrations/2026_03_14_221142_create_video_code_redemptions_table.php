<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('video_code_redemptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('batch_id')->constrained('video_code_batches')->cascadeOnDelete()->cascadeOnUpdate();
            $table->unsignedInteger('sequence_number');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('video_access_id')->nullable()->constrained('video_accesses')->nullOnDelete();
            $table->timestamp('redeemed_at');
            $table->timestamps();

            $table->unique(['batch_id', 'sequence_number']);
            $table->index(['user_id', 'batch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_code_redemptions');
    }
};
