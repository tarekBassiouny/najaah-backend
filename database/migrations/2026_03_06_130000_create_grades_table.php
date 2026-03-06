<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grades', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('center_id')
                ->constrained('centers')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->json('name_translations');
            $table->string('slug');
            $table->tinyInteger('stage');
            $table->unsignedInteger('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['center_id', 'slug'], 'grades_center_slug_unique');
            $table->index(['center_id', 'stage', 'is_active'], 'grades_center_stage_active_index');
            $table->index(['center_id', 'order'], 'grades_center_order_index');
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grades');
    }
};
