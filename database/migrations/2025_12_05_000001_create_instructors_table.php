<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instructors', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('center_id')
                ->nullable()
                ->constrained('centers')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->json('name_translations');
            $table->json('bio_translations')->nullable();
            $table->json('title_translations')->nullable();
            $table->string('avatar_url')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->json('social_links')->nullable();
            $table->foreignId('created_by')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instructors');
    }
};
