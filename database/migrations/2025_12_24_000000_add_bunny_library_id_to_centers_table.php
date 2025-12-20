<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('centers', 'bunny_library_id')) {
            Schema::table('centers', function (Blueprint $table): void {
                $table->unsignedBigInteger('bunny_library_id')->nullable()->after('device_limit');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('centers', 'bunny_library_id')) {
            Schema::table('centers', function (Blueprint $table): void {
                $table->dropColumn('bunny_library_id');
            });
        }
    }
};
