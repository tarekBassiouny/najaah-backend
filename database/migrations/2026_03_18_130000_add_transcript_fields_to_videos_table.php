<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('videos', function (Blueprint $table): void {
            $table->longText('transcript')->nullable()->after('thumbnail_urls');
            $table->string('transcript_format', 10)->nullable()->after('transcript');
            $table->string('transcript_source', 20)->nullable()->after('transcript_format');
        });
    }

    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table): void {
            $table->dropColumn(['transcript', 'transcript_format', 'transcript_source']);
        });
    }
};
