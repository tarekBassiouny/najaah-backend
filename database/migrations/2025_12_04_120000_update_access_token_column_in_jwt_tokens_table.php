<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE `jwt_tokens` MODIFY `access_token` TEXT NOT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE `jwt_tokens` MODIFY `access_token` VARCHAR(255) NOT NULL');
    }
};
