<?php

declare(strict_types=1);

namespace App\Services\Pdfs;

use App\Models\Pdf;
use App\Services\Pdfs\Contracts\PdfTextExtractionServiceInterface;
use App\Services\Storage\Contracts\StorageServiceInterface;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;

final class PdfTextExtractionService implements PdfTextExtractionServiceInterface
{
    public function __construct(
        private readonly StorageServiceInterface $storageService
    ) {}

    public function extractText(Pdf $pdf): string
    {
        if (! $this->canExtract($pdf)) {
            throw new RuntimeException('PDF text extraction is not supported for this file.');
        }

        $sourceId = is_string($pdf->source_id) ? $pdf->source_id : '';
        if ($sourceId === '') {
            throw new RuntimeException('PDF source is missing.');
        }

        $contents = $this->storageService->getContents($sourceId);
        $text = $this->extractFromContents($contents);

        return $this->normalizeExtractedText($text);
    }

    public function extractPageCount(Pdf $pdf): ?int
    {
        if (! $this->canExtract($pdf)) {
            return null;
        }

        $sourceId = is_string($pdf->source_id) ? $pdf->source_id : '';
        if ($sourceId === '') {
            return null;
        }

        try {
            $contents = $this->storageService->getContents($sourceId);

            if (class_exists(\Smalot\PdfParser\Parser::class)) {
                $document = (new \Smalot\PdfParser\Parser)->parseContent($contents);

                return count($document->getPages());
            }

            $tempPath = $this->writeTempPdf($contents);

            try {
                return $this->countPagesWithPdfinfo($tempPath);
            } finally {
                @unlink($tempPath);
            }
        } catch (\Throwable) {
            return null;
        }
    }

    private function countPagesWithPdfinfo(string $tempPath): ?int
    {
        if (! $this->commandAvailable('pdfinfo')) {
            return null;
        }

        $process = new Process(['pdfinfo', $tempPath]);
        $process->setTimeout(30);
        $process->run();

        if (! $process->isSuccessful()) {
            return null;
        }

        if (preg_match('/Pages:\s+(\d+)/', $process->getOutput(), $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    public function canExtract(Pdf $pdf): bool
    {
        return strtolower((string) $pdf->file_extension) === 'pdf'
            && is_string($pdf->source_id)
            && $pdf->source_id !== '';
    }

    private function extractFromContents(string $contents): string
    {
        if (class_exists(\Smalot\PdfParser\Parser::class)) {
            /** @var class-string<\Smalot\PdfParser\Parser> $parserClass */
            $parserClass = \Smalot\PdfParser\Parser::class;

            return (new $parserClass)->parseContent($contents)->getText();
        }

        $tempPath = $this->writeTempPdf($contents);

        try {
            if ($this->commandAvailable('pdftotext')) {
                return $this->extractWithPdftotext($tempPath);
            }

            return $this->extractWithStrings($tempPath);
        } finally {
            @unlink($tempPath);
        }
    }

    private function extractWithPdftotext(string $tempPath): string
    {
        $process = new Process(['pdftotext', '-layout', $tempPath, '-']);
        $process->setTimeout(60);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException('pdftotext failed to extract PDF text.');
        }

        return $process->getOutput();
    }

    private function extractWithStrings(string $tempPath): string
    {
        $process = new Process(['strings', '-n', '4', $tempPath]);
        $process->setTimeout(60);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException('strings failed to extract PDF text.');
        }

        return $process->getOutput();
    }

    private function commandAvailable(string $command): bool
    {
        $process = new Process(['which', $command]);
        $process->setTimeout(10);
        $process->run();

        return $process->isSuccessful();
    }

    private function writeTempPdf(string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), 'pdf-text-');
        if (! is_string($path)) {
            throw new RuntimeException('Unable to allocate a temporary PDF file.');
        }

        if (file_put_contents($path, $contents) === false) {
            @unlink($path);

            throw new RuntimeException('Unable to write a temporary PDF file.');
        }

        return $path;
    }

    private function normalizeExtractedText(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/[ \t]+/u', ' ', $text) ?? $text;
        $text = preg_replace("/\n{3,}/u", "\n\n", $text) ?? $text;

        $lines = array_map(
            static fn (string $line): string => trim($line),
            preg_split('/\n/u', $text) ?: []
        );

        return trim(Str::of(implode("\n", $lines))
            ->replaceMatches('/\n{3,}/u', "\n\n")
            ->toString());
    }
}
