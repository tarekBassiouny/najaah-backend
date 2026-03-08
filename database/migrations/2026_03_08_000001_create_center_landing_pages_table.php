<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('center_landing_pages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('center_id')
                ->constrained()
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->tinyInteger('status')->default(0); // 0=draft, 1=published

            // Meta section
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('meta_keywords')->nullable();

            // Hero section
            $table->json('hero_title_translations')->nullable();
            $table->json('hero_subtitle_translations')->nullable();
            $table->string('hero_background_url')->nullable();
            $table->string('hero_cta_text')->nullable();
            $table->string('hero_cta_url')->nullable();

            // About section
            $table->json('about_title_translations')->nullable();
            $table->json('about_content_translations')->nullable();
            $table->string('about_image_url')->nullable();

            // Contact section
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('contact_address')->nullable();

            // Social links
            $table->string('social_facebook')->nullable();
            $table->string('social_twitter')->nullable();
            $table->string('social_instagram')->nullable();
            $table->string('social_youtube')->nullable();
            $table->string('social_linkedin')->nullable();
            $table->string('social_tiktok')->nullable();

            // Styling
            $table->string('primary_color')->nullable();
            $table->string('secondary_color')->nullable();
            $table->string('font_family')->nullable();

            // Visibility toggles
            $table->boolean('show_hero')->default(true);
            $table->boolean('show_about')->default(true);
            $table->boolean('show_courses')->default(true);
            $table->boolean('show_testimonials')->default(true);
            $table->boolean('show_contact')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->unique('center_id');
            $table->index('status');
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('center_landing_pages');
    }
};
