<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('course_pdf', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_id')
                ->constrained('courses')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('pdf_id')
                ->constrained('pdfs')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('section_id')
                ->nullable()
                ->constrained('sections')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->foreignId('video_id')
                ->nullable()
                ->constrained('videos')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->unsignedInteger('order_index')->default(0);
            $table->boolean('visible')->default(true);
            $table->boolean('download_permission_override')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['course_id', 'pdf_id', 'section_id', 'video_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_pdf');
    }
};
