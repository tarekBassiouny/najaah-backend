<?php

declare(strict_types=1);

use App\Services\Bunny\BunnyLibraryService;
use App\Services\Bunny\BunnyStreamService;
use Tests\TestCase;

uses(TestCase::class);

it('binds bunny stream service when configuration is present', function (): void {
    config([
        'bunny.api.api_key' => 'test-key',
        'bunny.api.api_url' => 'https://video.bunnycdn.com',
        'bunny.api.library_id' => '',
    ]);

    app()->forgetInstance(BunnyStreamService::class);

    $service = app(BunnyStreamService::class);

    expect($service)->toBeInstanceOf(BunnyStreamService::class);
});

it('binds bunny library service when configuration is present', function (): void {
    config([
        'bunny.api.api_key' => 'test-key',
        'bunny.api.api_url' => 'https://video.bunnycdn.com',
    ]);

    app()->forgetInstance(BunnyLibraryService::class);

    $service = app(BunnyLibraryService::class);

    expect($service)->toBeInstanceOf(BunnyLibraryService::class);
});

it('throws when bunny stream configuration is missing', function (): void {
    config([
        'bunny.api.api_key' => '',
        'bunny.api.api_url' => '',
        'bunny.api.library_id' => '',
    ]);

    app()->forgetInstance(BunnyStreamService::class);

    expect(fn () => app(BunnyStreamService::class))
        ->toThrow(RuntimeException::class);
});
