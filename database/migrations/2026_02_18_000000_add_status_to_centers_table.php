<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('centers', 'status')) {
            return;
        }

        Schema::table('centers', function (Blueprint $table): void {
            $table->unsignedTinyInteger('status')->default(1)->after('is_demo');
            $table->index('status');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('centers', 'status')) {
            return;
        }

        Schema::table('centers', function (Blueprint $table): void {
            $table->dropIndex(['status']);
            $table->dropColumn('status');
        });
    }
};
