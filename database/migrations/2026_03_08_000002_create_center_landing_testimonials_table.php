<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('center_landing_testimonials', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('center_landing_page_id')
                ->constrained('center_landing_pages')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->string('author_name');
            $table->string('author_title')->nullable();
            $table->string('author_image_url')->nullable();
            $table->json('content_translations');
            $table->unsignedTinyInteger('rating')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index('center_landing_page_id');
            $table->index('sort_order');
            $table->index('is_active');
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('center_landing_testimonials');
    }
};
