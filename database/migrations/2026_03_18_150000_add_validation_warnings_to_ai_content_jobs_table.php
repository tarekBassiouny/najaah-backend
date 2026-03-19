<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_content_jobs', function (Blueprint $table): void {
            $table->json('validation_warnings')->nullable()->after('reviewed_payload');
        });
    }

    public function down(): void
    {
        Schema::table('ai_content_jobs', function (Blueprint $table): void {
            $table->dropColumn('validation_warnings');
        });
    }
};
