<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table): void {
            if (Schema::hasColumn('courses', 'instructor_translations')) {
                $table->dropColumn('instructor_translations');
            }

            $table->foreignId('primary_instructor_id')
                ->nullable()
                ->after('course_code')
                ->constrained('instructors')
                ->cascadeOnUpdate()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table): void {
            if (Schema::hasColumn('courses', 'primary_instructor_id')) {
                $table->dropForeign(['primary_instructor_id']);
                $table->dropColumn('primary_instructor_id');
            }

            $table->json('instructor_translations')->nullable();
        });
    }
};
