<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evolution_webhook_logs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('request_id')->nullable()->index();
            $table->string('instance')->nullable()->index();
            $table->string('event')->nullable()->index();
            $table->string('status', 32)->index();
            $table->unsignedSmallInteger('response_code')->nullable()->index();
            $table->text('error_message')->nullable();
            $table->json('headers')->nullable();
            $table->json('payload');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evolution_webhook_logs');
    }
};
