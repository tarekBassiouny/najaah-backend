<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('video_accesses', function (Blueprint $table): void {
            $table->unsignedInteger('total_view_limit')->nullable()->after('video_access_code_id');
        });
    }

    public function down(): void
    {
        Schema::table('video_accesses', function (Blueprint $table): void {
            $table->dropColumn('total_view_limit');
        });
    }
};
