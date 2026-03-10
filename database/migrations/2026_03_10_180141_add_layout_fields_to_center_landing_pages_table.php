<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('center_landing_pages', function (Blueprint $table): void {
            $table->json('section_order')->nullable()->after('show_contact');
            $table->json('section_layouts')->nullable()->after('section_order');
            $table->json('section_styles')->nullable()->after('section_layouts');
        });
    }

    public function down(): void
    {
        Schema::table('center_landing_pages', function (Blueprint $table): void {
            $table->dropColumn(['section_order', 'section_layouts', 'section_styles']);
        });
    }
};
