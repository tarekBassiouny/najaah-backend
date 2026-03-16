<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\VideoCodeBatch;
use App\Services\VideoAccess\Contracts\VideoCodeBatchServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendVideoCodeBatchCsvToWhatsAppJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(
        public readonly int $batchId,
        public readonly string $historyRecordId,
        public readonly string $phoneNumber
    ) {}

    public function handle(VideoCodeBatchServiceInterface $service): void
    {
        /** @var VideoCodeBatch|null $batch */
        $batch = VideoCodeBatch::query()->find($this->batchId);

        if (! $batch instanceof VideoCodeBatch) {
            return;
        }

        $service->processCsvWhatsAppSend($batch, $this->historyRecordId, $this->phoneNumber);
    }
}
