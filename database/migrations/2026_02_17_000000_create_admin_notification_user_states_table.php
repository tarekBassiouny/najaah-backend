<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_notifications', function (Blueprint $table): void {
            $table->dropColumn('read_at');
        });

        Schema::create('admin_notification_user_states', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('admin_notification_id')
                ->constrained('admin_notifications')
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['admin_notification_id', 'user_id'], 'admin_notification_user_states_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_notification_user_states');

        Schema::table('admin_notifications', function (Blueprint $table): void {
            $table->timestamp('read_at')->nullable()->after('data');
            $table->index(['user_id', 'read_at', 'created_at'], 'admin_notifications_polling_idx');
            $table->index(['center_id', 'read_at', 'created_at'], 'admin_notifications_center_polling_idx');
        });
    }
};
