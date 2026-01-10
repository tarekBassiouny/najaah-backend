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
        Schema::table('pdfs', function (Blueprint $table): void {
            if (! Schema::hasColumn('pdfs', 'center_id')) {
                $table->foreignId('center_id')->nullable()->after('id')->constrained('centers');
            }
        });

        DB::statement('
            UPDATE pdfs
            INNER JOIN users ON users.id = pdfs.created_by
            SET pdfs.center_id = users.center_id
            WHERE pdfs.center_id IS NULL
        ');

        $nullCount = DB::table('pdfs')->whereNull('center_id')->count();
        if ($nullCount === 0) {
            Schema::table('pdfs', function (Blueprint $table): void {
                $table->foreignId('center_id')->nullable(false)->change();
            });
        }
    }

    public function down(): void
    {
        Schema::table('pdfs', function (Blueprint $table): void {
            if (Schema::hasColumn('pdfs', 'center_id')) {
                $table->dropConstrainedForeignId('center_id');
            }
        });
    }
};
