<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quizzes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('center_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('course_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->json('title_translations');
            $table->json('description_translations')->nullable();
            $table->string('attachable_type', 50)->nullable();
            $table->unsignedBigInteger('attachable_id')->nullable();
            $table->decimal('passing_score', 5, 2)->default(70.00);
            $table->unsignedInteger('max_attempts')->default(0);
            $table->unsignedTinyInteger('attempt_score_policy')->default(0);
            $table->unsignedInteger('time_limit_minutes')->nullable();
            $table->boolean('shuffle_questions')->default(false);
            $table->boolean('shuffle_answers')->default(false);
            $table->boolean('show_correct_answers')->default(true);
            $table->boolean('show_score_immediately')->default(true);
            $table->boolean('is_required')->default(false);
            $table->boolean('is_active')->default(false);
            $table->timestamp('available_from')->nullable();
            $table->timestamp('available_until')->nullable();
            $table->unsignedInteger('order_index')->default(0);
            $table->foreignId('created_by')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['center_id', 'course_id']);
            $table->index(['attachable_type', 'attachable_id']);
            $table->index(['course_id', 'is_active', 'is_required']);
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quizzes');
    }
};
