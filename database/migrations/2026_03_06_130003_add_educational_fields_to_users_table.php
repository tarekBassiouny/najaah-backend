<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('grade_id')
                ->nullable()
                ->after('center_id')
                ->constrained('grades')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->foreignId('school_id')
                ->nullable()
                ->after('grade_id')
                ->constrained('schools')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->foreignId('college_id')
                ->nullable()
                ->after('school_id')
                ->constrained('colleges')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->index('grade_id');
            $table->index('school_id');
            $table->index('college_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['grade_id']);
            $table->dropIndex(['school_id']);
            $table->dropIndex(['college_id']);
            $table->dropConstrainedForeignId('grade_id');
            $table->dropConstrainedForeignId('school_id');
            $table->dropConstrainedForeignId('college_id');
        });
    }
};
