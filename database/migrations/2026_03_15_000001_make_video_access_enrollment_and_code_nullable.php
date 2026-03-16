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
        Schema::table('video_accesses', function (Blueprint $table): void {
            $table->dropForeign(['enrollment_id']);
            $table->dropForeign(['video_access_code_id']);
        });

        Schema::table('video_accesses', function (Blueprint $table): void {
            $table->foreignId('enrollment_id')->nullable()->change();
            $table->foreignId('video_access_code_id')->nullable()->change();
        });

        Schema::table('video_accesses', function (Blueprint $table): void {
            $table->foreign('enrollment_id')
                ->references('id')
                ->on('enrollments')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreign('video_access_code_id')
                ->references('id')
                ->on('video_access_codes')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        if (DB::table('video_accesses')->whereNull('enrollment_id')->exists()) {
            throw new RuntimeException('Cannot revert: video_accesses.enrollment_id contains null values.');
        }

        if (DB::table('video_accesses')->whereNull('video_access_code_id')->exists()) {
            throw new RuntimeException('Cannot revert: video_accesses.video_access_code_id contains null values.');
        }

        Schema::table('video_accesses', function (Blueprint $table): void {
            $table->dropForeign(['enrollment_id']);
            $table->dropForeign(['video_access_code_id']);
        });

        Schema::table('video_accesses', function (Blueprint $table): void {
            $table->foreignId('enrollment_id')->nullable(false)->change();
            $table->foreignId('video_access_code_id')->nullable(false)->change();
        });

        Schema::table('video_accesses', function (Blueprint $table): void {
            $table->foreign('enrollment_id')
                ->references('id')
                ->on('enrollments')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreign('video_access_code_id')
                ->references('id')
                ->on('video_access_codes')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
        });
    }
};
