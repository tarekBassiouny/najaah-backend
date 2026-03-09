<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignment_submissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('assignment_id')
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
            $table->foreignId('group_id')
                ->nullable()
                ->constrained('assignment_groups')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->unsignedTinyInteger('submission_type')->default(0);
            $table->text('text_content')->nullable();
            $table->string('link_url', 2048)->nullable();
            $table->unsignedTinyInteger('status')->default(0);
            $table->timestamp('submitted_at')->nullable();
            $table->boolean('is_late')->default(false);
            $table->unsignedInteger('days_late')->default(0);
            $table->decimal('score', 8, 2)->nullable();
            $table->decimal('score_after_penalty', 8, 2)->nullable();
            $table->boolean('passed')->default(false);
            $table->text('feedback')->nullable();
            $table->foreignId('graded_by')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->timestamp('graded_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['assignment_id', 'user_id']);
            $table->index(['user_id', 'enrollment_id']);
            $table->index(['center_id', 'status']);
            $table->index(['assignment_id', 'status']);
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignment_submissions');
    }
};
