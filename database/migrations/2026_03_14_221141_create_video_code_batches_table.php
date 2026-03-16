<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('video_code_batches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('video_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('center_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('batch_code', 16)->unique();
            $table->string('secret_key', 64);
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('sold_limit')->nullable();
            $table->unsignedInteger('redeemed_count')->default(0);
            $table->unsignedInteger('view_limit_per_code')->default(2);
            $table->tinyInteger('status')->default(0);
            $table->foreignId('generated_by')->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->timestamp('generated_at');
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['center_id', 'status']);
            $table->index(['video_id', 'status']);
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_code_batches');
    }
};
