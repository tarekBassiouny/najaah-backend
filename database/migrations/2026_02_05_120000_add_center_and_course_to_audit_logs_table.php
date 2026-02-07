<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->foreignId('center_id')
                ->nullable()
                ->after('user_id')
                ->constrained('centers')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->foreignId('course_id')
                ->nullable()
                ->after('center_id')
                ->constrained('courses')
                ->cascadeOnUpdate()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('course_id');
            $table->dropConstrainedForeignId('center_id');
        });
    }
};
