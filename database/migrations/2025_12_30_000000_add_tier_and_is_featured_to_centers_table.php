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
            $table->unsignedTinyInteger('tier')->default(0)->after('type');
            $table->boolean('is_featured')->default(false)->after('tier');
        });
    }

    public function down(): void
    {
        Schema::table('centers', function (Blueprint $table): void {
            $table->dropColumn(['tier', 'is_featured']);
        });
    }
};
