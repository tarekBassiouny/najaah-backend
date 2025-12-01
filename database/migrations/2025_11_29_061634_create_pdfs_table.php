<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pdfs', function (Blueprint $table): void {
            $table->id();
            $table->json('title_translations');
            $table->json('description_translations')->nullable();
            $table->tinyInteger('source_type'); // 0=url, 1=native
            $table->string('source_provider');
            $table->string('source_id')->nullable();
            $table->string('source_url')->nullable();
            $table->unsignedInteger('file_size_kb')->nullable();
            $table->string('file_extension');
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
        Schema::dropIfExists('pdfs');
    }
};
