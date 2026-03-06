<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schools', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('center_id')
                ->constrained('centers')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->json('name_translations');
            $table->string('slug');
            $table->tinyInteger('type')->default(0);
            $table->text('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['center_id', 'slug'], 'schools_center_slug_unique');
            $table->index(['center_id', 'type', 'is_active'], 'schools_center_type_active_index');
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schools');
    }
};
