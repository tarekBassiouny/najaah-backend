<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_grades', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_id')
                ->constrained('courses')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('grade_id')
                ->constrained('grades')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['course_id', 'grade_id']);
            $table->index('grade_id');
        });

        Schema::create('course_schools', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_id')
                ->constrained('courses')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('school_id')
                ->constrained('schools')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['course_id', 'school_id']);
            $table->index('school_id');
        });

        Schema::create('course_colleges', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_id')
                ->constrained('courses')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('college_id')
                ->constrained('colleges')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['course_id', 'college_id']);
            $table->index('college_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_colleges');
        Schema::dropIfExists('course_schools');
        Schema::dropIfExists('course_grades');
    }
};
