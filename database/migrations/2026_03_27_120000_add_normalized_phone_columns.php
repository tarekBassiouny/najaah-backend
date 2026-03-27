<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'phone_normalized')) {
                $table->string('phone_normalized')->nullable()->after('country_code');
            }

            if (! Schema::hasColumn('users', 'parent_phone_normalized')) {
                $table->string('parent_phone_normalized')->nullable()->after('parent_phone');
            }
        });

        Schema::table('users', function (Blueprint $table): void {
            if (! $this->indexExists('users', 'users_scope_phone_normalized_index')) {
                $table->index(['center_id', 'is_student', 'phone_normalized'], 'users_scope_phone_normalized_index');
            }

            if (! $this->indexExists('users', 'users_parent_phone_normalized_index')) {
                $table->index('parent_phone_normalized', 'users_parent_phone_normalized_index');
            }
        });

        Schema::table('otp_codes', function (Blueprint $table): void {
            if (! Schema::hasColumn('otp_codes', 'phone_normalized')) {
                $table->string('phone_normalized')->nullable()->after('country_code');
            }
        });

        Schema::table('otp_codes', function (Blueprint $table): void {
            if (! $this->indexExists('otp_codes', 'otp_codes_phone_normalized_token')) {
                $table->index(['phone_normalized', 'otp_token'], 'otp_codes_phone_normalized_token');
            }
        });
    }

    public function down(): void
    {
        Schema::table('otp_codes', function (Blueprint $table): void {
            if ($this->indexExists('otp_codes', 'otp_codes_phone_normalized_token')) {
                $table->dropIndex('otp_codes_phone_normalized_token');
            }
        });

        Schema::table('otp_codes', function (Blueprint $table): void {
            if (Schema::hasColumn('otp_codes', 'phone_normalized')) {
                $table->dropColumn('phone_normalized');
            }
        });

        Schema::table('users', function (Blueprint $table): void {
            if ($this->indexExists('users', 'users_scope_phone_normalized_index')) {
                $table->dropIndex('users_scope_phone_normalized_index');
            }

            if ($this->indexExists('users', 'users_parent_phone_normalized_index')) {
                $table->dropIndex('users_parent_phone_normalized_index');
            }
        });

        Schema::table('users', function (Blueprint $table): void {
            $columns = array_values(array_filter([
                Schema::hasColumn('users', 'phone_normalized') ? 'phone_normalized' : null,
                Schema::hasColumn('users', 'parent_phone_normalized') ? 'parent_phone_normalized' : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->where('index_name', $index)
            ->exists();
    }
};
