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
            $table->unsignedInteger('estimated_input_tokens')->default(0)->after('ai_model');
            $table->unsignedInteger('estimated_output_tokens')->default(0)->after('estimated_input_tokens');
            $table->index(['center_id', 'ai_provider', 'created_at'], 'ai_jobs_center_provider_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('ai_content_jobs', function (Blueprint $table): void {
            $table->dropIndex('ai_jobs_center_provider_created_idx');
            $table->dropColumn(['estimated_input_tokens', 'estimated_output_tokens']);
        });
    }
};
