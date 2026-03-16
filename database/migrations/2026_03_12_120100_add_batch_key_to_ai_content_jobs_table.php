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
            $table->uuid('batch_key')->nullable()->after('course_id');
            $table->index(['center_id', 'batch_key']);
        });
    }

    public function down(): void
    {
        Schema::table('ai_content_jobs', function (Blueprint $table): void {
            $table->dropIndex(['center_id', 'batch_key']);
            $table->dropColumn('batch_key');
        });
    }
};
