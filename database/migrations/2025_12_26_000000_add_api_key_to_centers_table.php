<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('centers', function (Blueprint $table): void {
            $table->string('api_key')->nullable()->unique()->after('slug');
        });
    }

    public function down(): void
    {
        Schema::table('centers', function (Blueprint $table): void {
            $table->dropUnique(['api_key']);
            $table->dropColumn('api_key');
        });
    }
};
