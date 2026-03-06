<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('videos', 'custom_thumbnail_url')) {
            return;
        }

        Schema::table('videos', function (Blueprint $table): void {
            $table->string('custom_thumbnail_url')->nullable()->after('thumbnail_url');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('videos', 'custom_thumbnail_url')) {
            return;
        }

        Schema::table('videos', function (Blueprint $table): void {
            $table->dropColumn('custom_thumbnail_url');
        });
    }
};
