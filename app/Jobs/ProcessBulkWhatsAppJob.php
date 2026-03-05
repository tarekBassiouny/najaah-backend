<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\BulkJobStatus;
use App\Models\BulkWhatsAppJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessBulkWhatsAppJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly int $bulkJobId
    ) {}

    public function handle(): void
    {
        /** @var BulkWhatsAppJob|null $job */
        $job = BulkWhatsAppJob::query()->with(['items'])->find($this->bulkJobId);

        if (! $job instanceof BulkWhatsAppJob) {
            return;
        }

        if (in_array($job->status, [BulkJobStatus::Cancelled, BulkJobStatus::Completed, BulkJobStatus::Paused], true)) {
            return;
        }

        $settings = is_array($job->settings) ? $job->settings : [];
        $batchSize = max(1, (int) ($settings['batch_size'] ?? 50));
        $delaySeconds = max(0, (int) ($settings['delay_seconds'] ?? 3));
        $batchPause = max(0, (int) ($settings['batch_pause_seconds'] ?? 60));

        $job->status = BulkJobStatus::Processing;
        if ($job->started_at === null) {
            $job->started_at = now();
        }

        $job->save();

        $items = $job->items()->pending()->orderBy('id')->get();

        if ($items->isEmpty()) {
            $job->status = BulkJobStatus::Completed;
            $job->completed_at = now();
            $job->save();

            return;
        }

        $totalDelay = 0;

        foreach ($items->chunk($batchSize) as $batch) {
            foreach ($batch->values() as $index => $item) {
                $itemDelay = $totalDelay + ($index * $delaySeconds);

                SendSingleWhatsAppCodeJob::dispatch((int) $item->id)
                    ->delay(now()->addSeconds($itemDelay));
            }

            $totalDelay += ($batch->count() * $delaySeconds) + $batchPause;
        }
    }
}
