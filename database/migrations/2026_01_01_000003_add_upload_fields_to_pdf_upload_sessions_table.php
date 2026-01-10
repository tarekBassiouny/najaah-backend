<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pdf_upload_sessions', function (Blueprint $table): void {
            if (! Schema::hasColumn('pdf_upload_sessions', 'upload_status')) {
                $table->tinyInteger('upload_status')->default(0)->after('object_key');
            }
            if (! Schema::hasColumn('pdf_upload_sessions', 'error_message')) {
                $table->string('error_message')->nullable()->after('upload_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pdf_upload_sessions', function (Blueprint $table): void {
            if (Schema::hasColumn('pdf_upload_sessions', 'error_message')) {
                $table->dropColumn('error_message');
            }
            if (Schema::hasColumn('pdf_upload_sessions', 'upload_status')) {
                $table->dropColumn('upload_status');
            }
        });
    }
};
