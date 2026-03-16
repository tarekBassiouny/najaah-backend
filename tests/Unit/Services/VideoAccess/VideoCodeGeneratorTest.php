<?php

declare(strict_types=1);

use App\Models\VideoCodeBatch;
use App\Services\VideoAccess\VideoCodeGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class)->group('video-code-generator', 'unit');

it('generates a 12 character code in 4-4-4 format and parses it with or without dashes', function (): void {
    $batch = VideoCodeBatch::factory()->create([
        'batch_code' => 'ABCD',
        'quantity' => 25,
    ]);

    $generator = app(VideoCodeGenerator::class);
    $code = $generator->generateCode($batch, 7);

    expect($code)->toMatch('/^[A-HJ-NP-Z2-9]{4}-[A-HJ-NP-Z2-9]{4}-[A-HJ-NP-Z2-9]{4}$/');

    $parsedFormatted = $generator->parseAndValidate($code);
    $parsedRaw = $generator->parseAndValidate(str_replace('-', '', strtolower($code)));

    expect($parsedFormatted)->not->toBeNull()
        ->and($parsedFormatted['batch']->id)->toBe($batch->id)
        ->and($parsedFormatted['sequence'])->toBe(7)
        ->and($parsedRaw)->not->toBeNull()
        ->and($parsedRaw['batch']->id)->toBe($batch->id)
        ->and($parsedRaw['sequence'])->toBe(7);
});

it('rejects a tampered code', function (): void {
    $batch = VideoCodeBatch::factory()->create([
        'batch_code' => 'WXYZ',
        'quantity' => 10,
    ]);

    $generator = app(VideoCodeGenerator::class);
    $code = $generator->generateCode($batch, 3);
    $tampered = substr($code, 0, -1).'A';

    expect($generator->parseAndValidate($tampered))->toBeNull();
});

it('generates 4 character batch tokens for new batches', function (): void {
    $generator = app(VideoCodeGenerator::class);
    $batchCode = $generator->generateBatchCode(3);

    expect($batchCode)->toMatch('/^[A-HJ-NP-Z2-9]{4}$/');
});
