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
        Schema::table('survey_responses', function (Blueprint $table): void {
            $table->dropForeign(['center_id']);
        });

        Schema::table('survey_responses', function (Blueprint $table): void {
            $table->foreignId('center_id')->nullable()->change();
        });

        Schema::table('survey_responses', function (Blueprint $table): void {
            $table->foreign('center_id')
                ->references('id')
                ->on('centers')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        if (DB::table('survey_responses')->whereNull('center_id')->exists()) {
            throw new RuntimeException('Cannot revert: survey_responses.center_id contains null values.');
        }

        Schema::table('survey_responses', function (Blueprint $table): void {
            $table->dropForeign(['center_id']);
        });

        Schema::table('survey_responses', function (Blueprint $table): void {
            $table->foreignId('center_id')->nullable(false)->change();
        });

        Schema::table('survey_responses', function (Blueprint $table): void {
            $table->foreign('center_id')
                ->references('id')
                ->on('centers')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
        });
    }
};

