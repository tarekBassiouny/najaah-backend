<?php

declare(strict_types=1);

use App\Jobs\CreateBunnyLibraryJob;
use App\Models\Center;
use App\Services\Bunny\BunnyLibraryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class)->group('jobs', 'bunny');

it('skips bunny library creation when already present', function (): void {
    $center = Center::factory()->create(['bunny_library_id' => 999]);

    $job = new CreateBunnyLibraryJob($center->id);

    $this->mock(BunnyLibraryService::class, function (MockInterface $mock): void {
        $mock->shouldNotReceive('createLibrary');
    });

    $job->handle(app(BunnyLibraryService::class));

    $center->refresh();
    expect($center->bunny_library_id)->toBe(999);
});

it('creates bunny library when missing', function (): void {
    $center = Center::factory()->create(['bunny_library_id' => null]);

    $job = new CreateBunnyLibraryJob($center->id);

    $this->mock(BunnyLibraryService::class, function (MockInterface $mock): void {
        $mock->shouldReceive('createLibrary')
            ->once()
            ->andReturn(['id' => 321]);
    });

    $job->handle(app(BunnyLibraryService::class));

    $center->refresh();
    expect($center->bunny_library_id)->toBe(321);
});

it('marks center failed when job fails', function (): void {
    $center = Center::factory()->create(['onboarding_status' => Center::ONBOARDING_IN_PROGRESS]);

    $job = new CreateBunnyLibraryJob($center->id);
    $job->failed(new RuntimeException('fail'));

    $center->refresh();
    expect($center->onboarding_status)->toBe(Center::ONBOARDING_FAILED);
});
