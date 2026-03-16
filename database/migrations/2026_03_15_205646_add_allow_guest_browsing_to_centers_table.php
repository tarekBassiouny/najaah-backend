<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('centers', function (Blueprint $table) {
            $table->boolean('allow_guest_browsing')->default(false)->after('device_limit');
            $table->index('allow_guest_browsing');
        });
    }

    public function down(): void
    {
        Schema::table('centers', function (Blueprint $table) {
            $table->dropIndex(['allow_guest_browsing']);
            $table->dropColumn('allow_guest_browsing');
        });
    }
};
