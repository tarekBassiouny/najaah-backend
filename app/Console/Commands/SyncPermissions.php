<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Permission;
use Illuminate\Console\Command;

class SyncPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permissions:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync permissions from config to database (idempotent, non-destructive, preserves IDs)';

    /**
     * Execute the console command.
     *
     * ID Preservation Guarantees:
     * - Existing permissions are looked up by NAME, not position
     * - Existing permission IDs are NEVER changed
     * - New permissions get the next auto-increment ID (appended)
     * - Permissions are NEVER deleted (only added or description updated)
     */
    public function handle(): int
    {
        $this->info('Syncing permissions from config/permissions.php...');
        $this->line('<fg=gray>  (IDs are preserved - only new permissions are appended)</>');
        $this->newLine();

        /** @var array<string, string> $permissions */
        $permissions = config('permissions', []);

        if (empty($permissions)) {
            $this->error('No permissions found in config/permissions.php');

            return self::FAILURE;
        }

        $created = 0;
        $updated = 0;
        $unchanged = 0;

        foreach ($permissions as $name => $description) {
            // Lookup by NAME ensures existing IDs are never changed
            $permission = Permission::withTrashed()->where('name', $name)->first();

            if (! $permission) {
                // New permission - gets next auto-increment ID
                $newPermission = Permission::create([
                    'name' => $name,
                    'description' => $description,
                ]);
                $this->line("  <fg=green>+ Created:</> {$name} <fg=gray>(id: {$newPermission->id})</>");
                $created++;
            } elseif ($permission->trashed()) {
                // Restore with SAME original ID
                $permission->restore();
                $permission->update(['description' => $description]);
                $this->line("  <fg=yellow>↻ Restored:</> {$name} <fg=gray>(id: {$permission->id} preserved)</>");
                $updated++;
            } elseif ($permission->description !== $description) {
                // Update description only - ID unchanged
                $permission->update(['description' => $description]);
                $this->line("  <fg=blue>~ Updated:</> {$name} <fg=gray>(id: {$permission->id} preserved)</>");
                $updated++;
            } else {
                $unchanged++;
            }
        }

        $this->newLine();
        $this->info('Summary:');
        $this->line("  Created: {$created}");
        $this->line("  Updated: {$updated}");
        $this->line("  Unchanged: {$unchanged}");
        $this->line('  Total in config: '.count($permissions));

        $dbCount = Permission::count();
        if ($dbCount > count($permissions)) {
            $extra = $dbCount - count($permissions);
            $this->newLine();
            $this->warn("Note: {$extra} permission(s) in database not in config (kept for safety)");
        }

        $this->newLine();
        $this->info('Permissions synced successfully. All existing IDs preserved.');

        return self::SUCCESS;
    }
}
