<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_questions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('quiz_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->json('question_translations');
            $table->unsignedTinyInteger('question_type')->default(0);
            $table->json('explanation_translations')->nullable();
            $table->decimal('points', 5, 2)->default(1.00);
            $table->unsignedInteger('order_index')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('ai_generated')->default(false);
            $table->string('ai_source_type', 50)->nullable();
            $table->unsignedBigInteger('ai_source_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['quiz_id', 'order_index']);
            $table->index(['quiz_id', 'is_active']);
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_questions');
    }
};
