<?php

declare(strict_types=1);

use App\Enums\VideoUploadStatus;
use App\Jobs\FetchBunnyVideoMetadataJob;
use App\Models\Center;
use App\Models\User;
use App\Models\Video;
use App\Services\Bunny\BunnyStreamService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class)->group('jobs', 'bunny');

it('stores thumbnail url from thumbnailFileName using configured base url', function (): void {
    config(['bunny.thumbnail_base_url' => 'https://vz-1fdcc0ad-1b7.b-cdn.net']);

    $center = Center::factory()->create();
    $creator = User::factory()->create(['center_id' => $center->id, 'is_student' => false]);
    $video = Video::factory()->create([
        'center_id' => $center->id,
        'created_by' => $creator->id,
        'library_id' => 563514,
        'source_id' => 'faf026da-ea10-4ad3-9ee9-978d80fbab44',
        'thumbnail_url' => null,
        'duration_seconds' => null,
        'encoding_status' => VideoUploadStatus::Ready,
    ]);

    $service = \Mockery::mock(BunnyStreamService::class);
    $service->shouldReceive('getVideo')
        ->once()
        ->with('faf026da-ea10-4ad3-9ee9-978d80fbab44', 563514)
        ->andReturn([
            'thumbnailFileName' => 'thumbnail.jpg',
            'length' => 321.4,
        ]);
    $this->app->instance(BunnyStreamService::class, $service);

    $job = new FetchBunnyVideoMetadataJob('faf026da-ea10-4ad3-9ee9-978d80fbab44', 563514, $center->id);
    $job->handle($service);

    $video->refresh();
    expect($video->thumbnail_url)->toBe('https://vz-1fdcc0ad-1b7.b-cdn.net/faf026da-ea10-4ad3-9ee9-978d80fbab44/thumbnail.jpg')
        ->and($video->duration_seconds)->toBeInt()
        ->and($video->duration_seconds)->toBe(321);
});

it('does not persist zero duration while bunny metadata is still processing and retries', function (): void {
    config(['bunny.thumbnail_base_url' => 'https://vz-1fdcc0ad-1b7.b-cdn.net']);

    $center = Center::factory()->create();
    $creator = User::factory()->create(['center_id' => $center->id, 'is_student' => false]);
    $video = Video::factory()->create([
        'center_id' => $center->id,
        'created_by' => $creator->id,
        'library_id' => 563514,
        'source_id' => '73ac1e58-2050-4019-8cd1-0f543cfeb2d9',
        'thumbnail_url' => null,
        'duration_seconds' => null,
        'encoding_status' => VideoUploadStatus::Ready,
    ]);

    $service = \Mockery::mock(BunnyStreamService::class);
    $service->shouldReceive('getVideo')
        ->once()
        ->with('73ac1e58-2050-4019-8cd1-0f543cfeb2d9', 563514)
        ->andReturn([
            'status' => 2,
            'encodeProgress' => 0,
            'thumbnailFileName' => 'thumbnail.jpg',
            'length' => 0,
        ]);
    $this->app->instance(BunnyStreamService::class, $service);

    $job = new FetchBunnyVideoMetadataJob('73ac1e58-2050-4019-8cd1-0f543cfeb2d9', 563514, $center->id);

    expect(fn () => $job->handle($service))
        ->toThrow(RuntimeException::class, 'Bunny metadata duration is not ready yet.');

    $video->refresh();
    expect($video->thumbnail_url)->toBe('https://vz-1fdcc0ad-1b7.b-cdn.net/73ac1e58-2050-4019-8cd1-0f543cfeb2d9/thumbnail.jpg')
        ->and($video->duration_seconds)->toBeNull();
});
