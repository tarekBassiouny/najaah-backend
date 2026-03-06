<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bulk_whatsapp_job_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('bulk_job_id')->constrained('bulk_whatsapp_jobs')->cascadeOnDelete();
            $table->foreignId('video_access_code_id')->constrained()->cascadeOnDelete();
            $table->tinyInteger('status')->default(0);
            $table->text('error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamps();

            $table->index(['bulk_job_id', 'status']);
            $table->unique(['bulk_job_id', 'video_access_code_id'], 'bulk_job_item_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bulk_whatsapp_job_items');
    }
};
