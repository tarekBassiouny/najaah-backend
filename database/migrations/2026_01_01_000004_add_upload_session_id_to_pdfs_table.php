<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pdfs', function (Blueprint $table): void {
            if (! Schema::hasColumn('pdfs', 'upload_session_id')) {
                $table->foreignId('upload_session_id')
                    ->nullable()
                    ->after('file_extension')
                    ->constrained('pdf_upload_sessions')
                    ->cascadeOnUpdate()
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('pdfs', function (Blueprint $table): void {
            if (Schema::hasColumn('pdfs', 'upload_session_id')) {
                $table->dropConstrainedForeignId('upload_session_id');
            }
        });
    }
};
