<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\AdminNotification;
use App\Models\AdminNotificationUserState;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class CleanupAdminNotifications extends Command
{
    protected $signature = 'notifications:cleanup';

    protected $description = 'Soft-delete admin notifications according to retention policy.';

    public function handle(): int
    {
        $now = Carbon::now();
        $readThreshold = $now->copy()->subDays(30);
        $unreadThreshold = $now->copy()->subDays(90);

        $readDeleted = AdminNotificationUserState::query()
            ->whereNotNull('read_at')
            ->where('read_at', '<=', $readThreshold)
            ->delete();

        $unreadDeleted = AdminNotification::query()
            ->where('created_at', '<=', $unreadThreshold)
            ->delete();

        Log::channel('domain')->info('notifications_cleanup', [
            'read_deleted' => $readDeleted,
            'unread_deleted' => $unreadDeleted,
        ]);

        $this->info('Admin notification cleanup complete.');

        return Command::SUCCESS;
    }
}
