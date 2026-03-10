<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignment_group_members', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('assignment_group_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->unsignedTinyInteger('role')->default(0);
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();

            $table->unique(['assignment_group_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignment_group_members');
    }
};
