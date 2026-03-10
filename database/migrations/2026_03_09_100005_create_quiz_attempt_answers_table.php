<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_attempt_answers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('quiz_attempt_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('quiz_question_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->json('selected_answer_ids')->nullable();
            $table->boolean('is_correct')->default(false);
            $table->decimal('points_earned', 5, 2)->default(0);
            $table->timestamp('answered_at')->nullable();
            $table->timestamps();

            $table->index('quiz_attempt_id');
            $table->index('quiz_question_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_attempt_answers');
    }
};
