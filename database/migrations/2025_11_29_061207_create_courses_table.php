<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('center_id')
                ->constrained('centers')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('category_id')
                ->nullable()
                ->constrained('categories')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->json('title_translations');
            $table->json('description_translations')->nullable();
            $table->json('instructor_translations')->nullable();
            $table->json('college_translations')->nullable();
            $table->string('grade_year')->nullable();
            $table->tinyInteger('difficulty_level'); // 0 draft, 1 uploading, etc. keep non-null per original
            $table->string('language', 10);
            $table->string('course_code')->nullable();
            $table->json('tags')->nullable();
            $table->tinyInteger('status')->default(0); // 0 draft, 1 uploading, 2 ready, 3 published, 4 archived
            $table->boolean('is_published')->default(false);
            $table->string('thumbnail_url')->nullable();
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->foreignId('created_by')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->unsignedBigInteger('cloned_from_id')->nullable();
            $table->timestamp('publish_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['center_id', 'course_code']);
            $table->foreign('cloned_from_id')
                ->references('id')
                ->on('courses')
                ->cascadeOnUpdate()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
