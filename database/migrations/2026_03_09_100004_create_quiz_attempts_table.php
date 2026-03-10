<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_attempts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('quiz_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('enrollment_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('center_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->unsignedInteger('attempt_number')->default(1);
            $table->unsignedTinyInteger('status')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->unsignedInteger('time_spent_seconds')->default(0);
            $table->decimal('score', 5, 2)->nullable();
            $table->decimal('points_earned', 8, 2)->default(0);
            $table->decimal('points_possible', 8, 2)->default(0);
            $table->boolean('passed')->default(false);
            $table->timestamps();

            $table->index(['quiz_id', 'user_id']);
            $table->index(['user_id', 'enrollment_id']);
            $table->index(['center_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_attempts');
    }
};
