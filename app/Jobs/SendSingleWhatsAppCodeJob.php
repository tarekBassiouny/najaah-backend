<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\BulkItemStatus;
use App\Enums\BulkJobStatus;
use App\Models\BulkWhatsAppJob;
use App\Models\BulkWhatsAppJobItem;
use App\Services\VideoAccess\Contracts\VideoApprovalCodeServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class SendSingleWhatsAppCodeJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly int $itemId
    ) {}

    public function handle(VideoApprovalCodeServiceInterface $codeService): void
    {
        /** @var BulkWhatsAppJobItem|null $item */
        $item = BulkWhatsAppJobItem::query()
            ->with(['bulkJob', 'videoAccessCode.user', 'videoAccessCode.video'])
            ->find($this->itemId);

        if (! $item instanceof BulkWhatsAppJobItem) {
            return;
        }

        /** @var BulkWhatsAppJob $bulkJob */
        $bulkJob = $item->bulkJob;

        if (in_array($bulkJob->status, [BulkJobStatus::Paused, BulkJobStatus::Cancelled, BulkJobStatus::Completed], true)) {
            return;
        }

        if ($item->status !== BulkItemStatus::Pending) {
            return;
        }

        $settings = is_array($bulkJob->settings) ? $bulkJob->settings : [];
        $maxRetries = max(0, (int) ($settings['max_retries'] ?? 2));
        $maxFailuresBeforePause = max(1, (int) ($settings['max_failures_before_pause'] ?? 10));

        try {
            $codeService->sendViaWhatsApp($item->videoAccessCode, $bulkJob->format);

            $item->status = BulkItemStatus::Sent;
            $item->sent_at = now();
            $item->error = null;
            $item->save();

            $bulkJob->increment('sent_count');
        } catch (\Throwable $throwable) {
            $attempts = (int) $item->attempts + 1;
            $item->attempts = $attempts;

            if ($attempts <= $maxRetries) {
                $item->status = BulkItemStatus::Pending;
                $item->error = Str::limit($throwable->getMessage(), 500);
                $item->save();

                self::dispatch($item->id)->delay(now()->addSeconds(3));

                return;
            }

            $item->status = BulkItemStatus::Failed;
            $item->error = Str::limit($throwable->getMessage(), 500);
            $item->save();

            $bulkJob->increment('failed_count');

            $bulkJob->refresh();
            if ($bulkJob->failed_count >= $maxFailuresBeforePause) {
                $bulkJob->status = BulkJobStatus::Paused;
                $bulkJob->save();

                return;
            }
        }

        $this->checkCompletion($bulkJob);
    }

    private function checkCompletion(BulkWhatsAppJob $job): void
    {
        $job->refresh();

        $pending = $job->items()->pending()->count();

        if ($pending === 0 && $job->status === BulkJobStatus::Processing) {
            $job->status = BulkJobStatus::Completed;
            $job->completed_at = now();
            $job->save();
        }
    }
}
