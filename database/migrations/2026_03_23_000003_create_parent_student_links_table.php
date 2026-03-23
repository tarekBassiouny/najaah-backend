<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parent_student_links', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('parent_user_id')->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('student_user_id')->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('center_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->tinyInteger('status')->default(0);
            $table->foreignId('linked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->tinyInteger('link_method')->default(0);
            $table->timestamp('linked_at');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['parent_user_id', 'student_user_id', 'center_id'], 'psl_parent_student_center_unique');
            $table->index('student_user_id');
            $table->index('center_id');
            $table->index('status');
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parent_student_links');
    }
};
