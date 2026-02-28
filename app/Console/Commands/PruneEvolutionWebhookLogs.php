<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\EvolutionWebhookLog;
use Illuminate\Console\Command;

class PruneEvolutionWebhookLogs extends Command
{
    protected $signature = 'evolution:webhooks:prune {--days=30 : Delete webhook logs older than this many days}';

    protected $description = 'Prune old persisted Evolution webhook logs.';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $cutoff = now()->subDays($days);

        $deleted = EvolutionWebhookLog::query()
            ->where('created_at', '<', $cutoff)
            ->delete();

        $this->info(sprintf(
            'Deleted %d Evolution webhook log(s) older than %d day(s).',
            $deleted,
            $days,
        ));

        return self::SUCCESS;
    }
}
