<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterAccessTokenLengthOnJwtTokensTable extends Migration
{
    public function up(): void
    {
        Schema::table('jwt_tokens', function (Blueprint $table): void {
            $table->longText('access_token')->change();
        });
    }

    public function down(): void
    {
        Schema::table('jwt_tokens', function (Blueprint $table): void {
            $table->string('access_token', 255)->change();
        });
    }
}
