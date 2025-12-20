<?php

declare(strict_types=1);

use App\Jobs\CreateCenterBunnyLibrary;
use App\Models\Center;
use App\Services\Bunny\BunnyLibraryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('creates and stores bunny library id for center', function (): void {
    $center = Center::factory()->create([
        'slug' => 'center-a',
        'bunny_library_id' => null,
    ]);

    $service = Mockery::mock(BunnyLibraryService::class);
    $service->shouldReceive('createLibrary')
        ->once()
        ->with("{$center->slug}-{$center->id}-testing")
        ->andReturn([
            'id' => 987,
            'raw' => [],
        ]);

    $job = new CreateCenterBunnyLibrary($center->id);
    $job->handle($service);

    $center->refresh();

    expect($center->bunny_library_id)->toBe(987)
        ->and($job->tries)->toBe(3);
});
