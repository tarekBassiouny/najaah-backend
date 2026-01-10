<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pdf_upload_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('center_id')->constrained('centers')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('object_key');
            $table->string('file_extension');
            $table->unsignedInteger('file_size_kb')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pdf_upload_sessions');
    }
};
