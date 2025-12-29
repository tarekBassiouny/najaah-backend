<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('centers', function (Blueprint $table): void {
            if (! Schema::hasColumn('centers', 'is_demo')) {
                $table->boolean('is_demo')->default(false)->after('is_featured');
            }
        });

        Schema::table('courses', function (Blueprint $table): void {
            if (! Schema::hasColumn('courses', 'is_demo')) {
                $table->boolean('is_demo')->default(false)->after('is_featured');
            }
        });

        Schema::table('sections', function (Blueprint $table): void {
            if (! Schema::hasColumn('sections', 'is_demo')) {
                $table->boolean('is_demo')->default(false)->after('visible');
            }
        });

        Schema::table('videos', function (Blueprint $table): void {
            if (! Schema::hasColumn('videos', 'is_demo')) {
                $table->boolean('is_demo')->default(false)->after('encoding_status');
            }
        });

        Schema::table('pdfs', function (Blueprint $table): void {
            if (! Schema::hasColumn('pdfs', 'is_demo')) {
                $table->boolean('is_demo')->default(false)->after('file_extension');
            }
        });
    }

    public function down(): void
    {
        Schema::table('centers', function (Blueprint $table): void {
            if (Schema::hasColumn('centers', 'is_demo')) {
                $table->dropColumn('is_demo');
            }
        });

        Schema::table('courses', function (Blueprint $table): void {
            if (Schema::hasColumn('courses', 'is_demo')) {
                $table->dropColumn('is_demo');
            }
        });

        Schema::table('sections', function (Blueprint $table): void {
            if (Schema::hasColumn('sections', 'is_demo')) {
                $table->dropColumn('is_demo');
            }
        });

        Schema::table('videos', function (Blueprint $table): void {
            if (Schema::hasColumn('videos', 'is_demo')) {
                $table->dropColumn('is_demo');
            }
        });

        Schema::table('pdfs', function (Blueprint $table): void {
            if (Schema::hasColumn('pdfs', 'is_demo')) {
                $table->dropColumn('is_demo');
            }
        });
    }
};
