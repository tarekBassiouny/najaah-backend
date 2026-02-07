<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('agent_executions')) {
            return;
        }

        if (Schema::hasTable('centers') && Schema::hasColumn('agent_executions', 'center_id')) {
            try {
                Schema::table('agent_executions', function (Blueprint $table): void {
                    $table->foreign('center_id', 'agent_executions_center_id_foreign')
                        ->references('id')
                        ->on('centers')
                        ->cascadeOnUpdate()
                        ->cascadeOnDelete();
                });
            } catch (QueryException) {
                // Constraint already exists in older environments.
            }
        }

        if (Schema::hasTable('users') && Schema::hasColumn('agent_executions', 'initiated_by')) {
            try {
                Schema::table('agent_executions', function (Blueprint $table): void {
                    $table->foreign('initiated_by', 'agent_executions_initiated_by_foreign')
                        ->references('id')
                        ->on('users')
                        ->cascadeOnUpdate()
                        ->cascadeOnDelete();
                });
            } catch (QueryException) {
                // Constraint already exists in older environments.
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('agent_executions')) {
            return;
        }

        try {
            Schema::table('agent_executions', function (Blueprint $table): void {
                $table->dropForeign('agent_executions_center_id_foreign');
            });
        } catch (QueryException) {
            // Constraint may not exist.
        }

        try {
            Schema::table('agent_executions', function (Blueprint $table): void {
                $table->dropForeign('agent_executions_initiated_by_foreign');
            });
        } catch (QueryException) {
            // Constraint may not exist.
        }
    }
};
