<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignments', function (Blueprint $table): void {
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
            $table->json('submission_types');
            $table->json('allowed_file_types')->nullable();
            $table->unsignedInteger('max_file_size_mb')->default(10);
            $table->unsignedInteger('max_files')->default(5);
            $table->boolean('is_group_assignment')->default(false);
            $table->unsignedInteger('max_group_size')->nullable();
            $table->decimal('max_points', 8, 2)->default(100.00);
            $table->decimal('passing_score', 5, 2)->default(60.00);
            $table->boolean('is_required')->default(false);
            $table->boolean('is_active')->default(false);
            $table->timestamp('due_date')->nullable();
            $table->boolean('late_submission_allowed')->default(false);
            $table->decimal('late_penalty_percent', 5, 2)->default(0.00);
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
        Schema::dropIfExists('assignments');
    }
};
