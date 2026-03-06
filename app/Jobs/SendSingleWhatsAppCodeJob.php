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
use Illuminate\Database\Eloquent\Builder;
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
        /** @var BulkWhatsAppJobItem|null $itemForTimeout */
        $itemForTimeout = BulkWhatsAppJobItem::query()
            ->with(['bulkJob'])
            ->find($this->itemId);

        if (! $itemForTimeout instanceof BulkWhatsAppJobItem) {
            return;
        }

        /** @var BulkWhatsAppJob $bulkJobForTimeout */
        $bulkJobForTimeout = $itemForTimeout->bulkJob;
        $settingsForTimeout = is_array($bulkJobForTimeout->settings) ? $bulkJobForTimeout->settings : [];
        $processingTimeoutSeconds = $this->resolveProcessingTimeoutSeconds($settingsForTimeout);
        $staleBefore = now()->subSeconds($processingTimeoutSeconds);

        $claimed = BulkWhatsAppJobItem::query()
            ->whereKey($this->itemId)
            ->where(static function (Builder $query) use ($staleBefore): void {
                $query
                    ->where('status', BulkItemStatus::Pending->value)
                    ->orWhere(static function (Builder $processing) use ($staleBefore): void {
                        $processing
                            ->where('status', BulkItemStatus::Processing->value)
                            ->where('updated_at', '<=', $staleBefore);
                    });
            })
            ->update([
                'status' => BulkItemStatus::Processing->value,
                'updated_at' => now(),
            ]);

        if ($claimed === 0) {
            return;
        }

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
            $item->status = BulkItemStatus::Pending;
            $item->save();

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

        $remaining = $job->items()
            ->whereIn('status', [BulkItemStatus::Pending->value, BulkItemStatus::Processing->value])
            ->count();

        if ($remaining === 0 && $job->status === BulkJobStatus::Processing) {
            $job->status = BulkJobStatus::Completed;
            $job->completed_at = now();
            $job->save();
        }
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function resolveProcessingTimeoutSeconds(array $settings): int
    {
        $configured = $settings['processing_timeout_seconds'] ?? null;
        if (is_numeric($configured)) {
            return max(30, (int) $configured);
        }

        $connection = (string) config('queue.default', 'database');
        $retryAfter = config('queue.connections.'.$connection.'.retry_after');
        if (! is_numeric($retryAfter)) {
            return 80;
        }

        return max(30, ((int) $retryAfter) - 10);
    }
}
