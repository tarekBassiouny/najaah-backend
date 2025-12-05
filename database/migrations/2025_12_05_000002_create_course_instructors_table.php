<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_instructors', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_id')
                ->constrained('courses')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('instructor_id')
                ->constrained('instructors')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->string('role')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['course_id', 'instructor_id', 'deleted_at'], 'course_instructor_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_instructors');
    }
};
