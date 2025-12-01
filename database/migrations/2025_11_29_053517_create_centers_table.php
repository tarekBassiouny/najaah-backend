<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('centers', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->tinyInteger('type'); // 0=unbranded, 1=branded
            $table->json('name_translations');
            $table->json('description_translations')->nullable();
            $table->string('logo_url')->nullable();
            $table->string('primary_color')->nullable();
            $table->unsignedInteger('default_view_limit')->default(2);
            $table->boolean('allow_extra_view_requests')->default(true);
            $table->boolean('pdf_download_permission')->default(false);
            $table->unsignedInteger('device_limit')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('centers');
    }
};
