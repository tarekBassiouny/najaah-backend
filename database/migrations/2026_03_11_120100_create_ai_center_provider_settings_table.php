<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_center_provider_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('center_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->string('provider_key', 50);
            $table->boolean('is_enabled')->default(true);
            $table->json('allowed_models')->nullable();
            $table->string('default_model', 120)->nullable();
            $table->json('limits')->nullable();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();
            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();
            $table->timestamps();

            $table->unique(['center_id', 'provider_key']);
            $table->index(['provider_key', 'is_enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_center_provider_settings');
    }
};
