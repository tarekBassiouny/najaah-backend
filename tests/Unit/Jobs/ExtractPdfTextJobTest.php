<?php

declare(strict_types=1);

use App\Enums\TextExtractionStatus;
use App\Jobs\ExtractPdfTextJob;
use App\Models\Pdf;
use App\Services\Pdfs\Contracts\PdfTextExtractionServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class)->group('pdfs', 'jobs');

afterEach(function (): void {
    \Mockery::close();
});

it('stores extracted text when pdf extraction succeeds', function (): void {
    $pdf = Pdf::factory()->create([
        'text_extraction_status' => TextExtractionStatus::Pending,
        'text_content' => null,
    ]);

    $service = \Mockery::mock(PdfTextExtractionServiceInterface::class);
    $service->shouldReceive('canExtract')->once()->andReturn(true);
    $service->shouldReceive('extractText')->once()->andReturn('Extracted lesson text');
    $service->shouldReceive('extractPageCount')->once()->andReturn(5);

    $job = new ExtractPdfTextJob($pdf);
    $job->handle($service);

    $pdf->refresh();
    expect($pdf->text_content)->toBe('Extracted lesson text')
        ->and($pdf->text_extraction_status)->toBe(TextExtractionStatus::Completed)
        ->and($pdf->page_count)->toBe(5);
});

it('marks pdf extraction as skipped when the file cannot be processed', function (): void {
    $pdf = Pdf::factory()->create([
        'text_extraction_status' => TextExtractionStatus::Pending,
        'text_content' => null,
    ]);

    $service = \Mockery::mock(PdfTextExtractionServiceInterface::class);
    $service->shouldReceive('canExtract')->once()->andReturn(false);
    $service->shouldNotReceive('extractText');

    $job = new ExtractPdfTextJob($pdf);
    $job->handle($service);

    $pdf->refresh();
    expect($pdf->text_content)->toBeNull()
        ->and($pdf->text_extraction_status)->toBe(TextExtractionStatus::Skipped);
});
