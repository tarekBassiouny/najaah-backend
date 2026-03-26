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
            $table->unsignedInteger('page_count')->nullable()->after('file_size_kb');
        });
    }

    public function down(): void
    {
        Schema::table('pdfs', function (Blueprint $table): void {
            $table->dropColumn('page_count');
        });
    }
};
