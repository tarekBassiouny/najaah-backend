<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\TextExtractionStatus;
use App\Models\Pdf;
use App\Services\Pdfs\Contracts\PdfTextExtractionServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ExtractPdfTextJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 300;

    /** @var array<int> */
    public array $backoff = [15, 60, 180];

    public function __construct(
        public readonly Pdf $pdf
    ) {
        $this->onQueue('pdf-extraction');
    }

    public function handle(PdfTextExtractionServiceInterface $service): void
    {
        $pdf = Pdf::query()->find($this->pdf->id);
        if (! $pdf instanceof Pdf) {
            return;
        }

        if (! $service->canExtract($pdf)) {
            $pdf->update([
                'text_extraction_status' => TextExtractionStatus::Skipped,
            ]);

            return;
        }

        $pdf->update([
            'text_extraction_status' => TextExtractionStatus::Processing,
        ]);

        try {
            $text = $service->extractText($pdf);
            $pageCount = $service->extractPageCount($pdf);

            $pdf->update([
                'text_content' => $text,
                'text_extraction_status' => $text === ''
                    ? TextExtractionStatus::Skipped
                    : TextExtractionStatus::Completed,
                ...($pageCount !== null ? ['page_count' => $pageCount] : []),
            ]);
        } catch (\Throwable $throwable) {
            Log::error('PDF text extraction failed', [
                'pdf_id' => $pdf->id,
                'error' => $throwable->getMessage(),
            ]);

            $pdf->update([
                'text_extraction_status' => TextExtractionStatus::Failed,
            ]);

            throw $throwable;
        }
    }
}
