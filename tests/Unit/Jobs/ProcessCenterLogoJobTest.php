<?php

declare(strict_types=1);

use App\Jobs\ProcessCenterLogoJob;
use App\Models\Center;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class)->group('jobs', 'logo');

it('skips processing when logo already processed', function (): void {
    $center = Center::factory()->create([
        'logo_url' => 'https://example.com/logo.png',
        'branding_metadata' => [
            'logo_source' => 'https://example.com/logo.png',
            'logo_processed_at' => now()->toISOString(),
        ],
    ]);

    $job = new ProcessCenterLogoJob($center->id, 'https://example.com/logo.png');
    $job->handle();

    $center->refresh();
    expect($center->branding_metadata['logo_processed_at'] ?? null)->not->toBeNull();
});

it('records logo processing metadata when needed', function (): void {
    $center = Center::factory()->create([
        'logo_url' => 'https://example.com/logo.png',
        'branding_metadata' => null,
    ]);

    $job = new ProcessCenterLogoJob($center->id, 'https://example.com/logo.png');
    $job->handle();

    $center->refresh();
    expect($center->branding_metadata['logo_source'] ?? null)->toBe('https://example.com/logo.png')
        ->and($center->branding_metadata['logo_processed_at'] ?? null)->not->toBeNull();
});

it('marks center failed when job fails', function (): void {
    $center = Center::factory()->create(['onboarding_status' => Center::ONBOARDING_IN_PROGRESS]);

    $job = new ProcessCenterLogoJob($center->id, 'https://example.com/logo.png');
    $job->failed(new RuntimeException('fail'));

    $center->refresh();
    expect($center->onboarding_status)->toBe(Center::ONBOARDING_FAILED);
});
