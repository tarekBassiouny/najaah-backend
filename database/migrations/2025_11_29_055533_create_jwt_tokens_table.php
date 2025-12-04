<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jwt_tokens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('device_id')
                ->nullable()
                ->constrained('user_devices')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->text('access_token');
            $table->string('refresh_token', 255);
            $table->timestamp('expires_at');
            $table->timestamp('refresh_expires_at');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['device_id', 'refresh_token']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jwt_tokens');
    }
};
