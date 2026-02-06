<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_executions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('center_id');
            $table->string('agent_type', 50); // content_publishing, enrollment, analytics, notification
            $table->string('target_type', 100)->nullable(); // Polymorphic: App\Models\Course, etc.
            $table->unsignedBigInteger('target_id')->nullable(); // Polymorphic ID
            $table->tinyInteger('status')->default(0); // 0=pending, 1=running, 2=completed, 3=failed
            $table->json('context')->nullable(); // Input parameters
            $table->json('result')->nullable(); // Execution result
            $table->json('steps_completed')->nullable(); // Array of completed step names
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('initiated_by');
            $table->timestamps();
            $table->softDeletes();

            $table->index('center_id');
            $table->index('agent_type');
            $table->index('status');
            $table->index('initiated_by');
            $table->index(['target_type', 'target_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_executions');
    }
};
