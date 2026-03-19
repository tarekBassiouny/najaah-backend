<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pdfs', function (Blueprint $table): void {
            $table->longText('text_content')->nullable()->after('file_extension');
            $table->unsignedTinyInteger('text_extraction_status')->default(0)->after('text_content');
            $table->index('text_extraction_status');
        });
    }

    public function down(): void
    {
        Schema::table('pdfs', function (Blueprint $table): void {
            $table->dropIndex(['text_extraction_status']);
            $table->dropColumn(['text_content', 'text_extraction_status']);
        });
    }
};
