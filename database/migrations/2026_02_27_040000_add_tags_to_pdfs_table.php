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
            if (! Schema::hasColumn('pdfs', 'tags')) {
                $table->json('tags')->nullable()->after('description_translations');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pdfs', function (Blueprint $table): void {
            if (Schema::hasColumn('pdfs', 'tags')) {
                $table->dropColumn('tags');
            }
        });
    }
};
