<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('otp_codes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->string('phone');
            $table->string('otp_code', 10);
            $table->string('otp_token', 255);
            $table->string('provider');
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['phone', 'otp_token']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_requests');
    }
};
