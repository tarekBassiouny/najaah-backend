<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table): void {
            $table->foreignId('center_id')
                ->nullable()
                ->after('id')
                ->constrained('centers')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
        });

        Schema::table('roles', function (Blueprint $table): void {
            $table->dropUnique('roles_slug_unique');
            $table->unique(['center_id', 'slug'], 'roles_center_slug_unique');
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table): void {
            $table->dropUnique('roles_center_slug_unique');
            $table->unique('slug', 'roles_slug_unique');
        });

        Schema::table('roles', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('center_id');
        });
    }
};
