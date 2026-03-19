<?php

declare(strict_types=1);

use App\Enums\TranscriptFormat;
use App\Services\Videos\TranscriptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class)->group('videos', 'services');

it('parses vtt content into plain text', function (): void {
    $service = app(TranscriptService::class);

    $result = $service->parseToPlainText(implode("\n", [
        'WEBVTT',
        '',
        '00:00:00.000 --> 00:00:02.000',
        'Lesson intro',
        '',
        '00:00:02.500 --> 00:00:04.000',
        'Second line',
    ]), TranscriptFormat::Vtt);

    expect($result)->toBe("Lesson intro\nSecond line");
});

it('parses srt content into plain text', function (): void {
    $service = app(TranscriptService::class);

    $result = $service->parseToPlainText(implode("\n", [
        '1',
        '00:00:00,000 --> 00:00:02,000',
        'Line one',
        '',
        '2',
        '00:00:02,500 --> 00:00:04,000',
        'Line two',
    ]), TranscriptFormat::Srt);

    expect($result)->toBe("Line one\nLine two");
});
