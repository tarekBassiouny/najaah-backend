<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\TextExtractionStatus;
use App\Jobs\ExtractPdfTextJob;
use App\Models\Pdf;
use Illuminate\Console\Command;

class BackfillPdfTextExtraction extends Command
{
    protected $signature = 'pdfs:backfill-text-extraction
        {--dry-run : Show how many PDFs need extraction without dispatching jobs}
        {--failed : Also re-process PDFs with failed/skipped extraction}';

    protected $description = 'Dispatch text extraction jobs for PDFs that have not been processed yet.';

    public function handle(): int
    {
        $query = Pdf::query()
            ->where('file_extension', 'pdf')
            ->whereNotNull('source_id')
            ->where('source_id', '!=', '');

        $includeFailed = (bool) $this->option('failed');

        if ($includeFailed) {
            $query->where(function ($builder): void {
                $builder->whereNull('text_extraction_status')
                    ->orWhereIn('text_extraction_status', [
                        TextExtractionStatus::Pending,
                        TextExtractionStatus::Failed,
                        TextExtractionStatus::Skipped,
                    ]);
            });
        } else {
            $query->where(function ($builder): void {
                $builder->whereNull('text_extraction_status')
                    ->orWhere('text_extraction_status', TextExtractionStatus::Pending);
            });
        }

        $count = (int) $query->count();

        if ($count === 0) {
            $this->info('No PDFs need text extraction.');

            return self::SUCCESS;
        }

        if ((bool) $this->option('dry-run')) {
            $this->info(sprintf('PDFs needing text extraction: %d', $count));

            return self::SUCCESS;
        }

        $this->info(sprintf('Dispatching text extraction for %d PDFs...', $count));
        $dispatched = 0;

        (clone $query)->orderBy('id')->chunkById(100, function ($pdfs) use (&$dispatched): void {
            foreach ($pdfs as $pdf) {
                $pdf->update([
                    'text_extraction_status' => TextExtractionStatus::Pending,
                ]);

                ExtractPdfTextJob::dispatch($pdf);
                $dispatched++;
            }
        });

        $this->info(sprintf('Dispatched %d text extraction jobs to the pdf-extraction queue.', $dispatched));
        $this->warn('Make sure a queue worker is processing the pdf-extraction queue:');
        $this->line('  php artisan queue:work --queue=pdf-extraction');

        return self::SUCCESS;
    }
}
