<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_generation_jobs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('quiz_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->string('source_type', 50);
            $table->unsignedBigInteger('source_id');
            $table->unsignedTinyInteger('status')->default(0);
            $table->unsignedInteger('questions_requested')->default(5);
            $table->unsignedInteger('questions_generated')->default(0);
            $table->string('ai_provider', 50)->nullable();
            $table->string('ai_model', 100)->nullable();
            $table->text('prompt_used')->nullable();
            $table->json('generated_questions')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('created_by')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->timestamps();

            $table->index('quiz_id');
            $table->index('status');
            $table->index(['source_type', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_generation_jobs');
    }
};
