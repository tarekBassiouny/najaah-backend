<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('otp_codes', function (Blueprint $table): void {
            $table->string('country_code', 8)->nullable()->after('phone');
            $table->index(['country_code', 'phone', 'otp_token'], 'otp_codes_country_phone_token');
        });
    }

    public function down(): void
    {
        Schema::table('otp_codes', function (Blueprint $table): void {
            $table->dropIndex('otp_codes_country_phone_token');
            $table->dropColumn('country_code');
        });
    }
};
