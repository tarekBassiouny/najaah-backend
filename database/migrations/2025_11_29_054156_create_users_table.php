<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('center_id')
                ->nullable()
                ->constrained('centers')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->string('name');
            $table->string('username')->nullable();
            $table->string('phone');
            $table->string('email')->nullable();
            $table->string('password');
            $table->tinyInteger('status')->default(1); // 0 inactive, 1 active, 2 banned
            $table->boolean('is_student')->default(false);
            $table->string('avatar_url')->nullable();
            $table->timestamp('last_login_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // ⭐ UNIQUE PER CENTER (correct business rule)
            $table->unique(['center_id', 'phone']);
            $table->unique(['center_id', 'email']);
            $table->index(['status', 'is_student']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
