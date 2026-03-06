<?php

declare(strict_types=1);

use App\Services\Storage\Contracts\StorageServiceInterface;
use App\Services\Videos\VideoThumbnailUrlResolver;
use Tests\TestCase;

uses(TestCase::class)->group('videos');

afterEach(function (): void {
    \Mockery::close();
});

it('returns null when the thumbnail path is missing', function (): void {
    $storage = \Mockery::mock(StorageServiceInterface::class);
    $resolver = new VideoThumbnailUrlResolver($storage);

    expect($resolver->resolve(null))->toBeNull();
});

it('returns absolute urls without touching storage', function (): void {
    $storage = \Mockery::mock(StorageServiceInterface::class);
    $storage->shouldNotReceive('exists');
    $storage->shouldNotReceive('temporaryUrl');
    $storage->shouldNotReceive('url');

    $resolver = new VideoThumbnailUrlResolver($storage);

    expect($resolver->resolve('https://example.com/thumb.png'))->toBe('https://example.com/thumb.png');
});

it('returns signed urls for private disks', function (): void {
    config()->set('filesystems.default', 'spaces');
    config()->set('filesystems.disks.spaces.visibility', 'private');
    config()->set('filesystems.signed_url_ttl', 600);

    $storage = \Mockery::mock(StorageServiceInterface::class);
    $storage->shouldNotReceive('exists');
    $storage->shouldReceive('temporaryUrl')->once()->with('centers/1/videos/3/thumbnail/test.png', 600)->andReturn('https://signed.test/thumb');

    $resolver = new VideoThumbnailUrlResolver($storage);

    expect($resolver->resolve('centers/1/videos/3/thumbnail/test.png'))->toBe('https://signed.test/thumb');
});

it('returns public urls for public disks', function (): void {
    config()->set('filesystems.default', 'public');
    config()->set('filesystems.disks.public.visibility', 'public');

    $storage = \Mockery::mock(StorageServiceInterface::class);
    $storage->shouldNotReceive('exists');
    $storage->shouldReceive('url')->once()->with('centers/1/videos/3/thumbnail/test.png')->andReturn('https://cdn.test/thumb.png');

    $resolver = new VideoThumbnailUrlResolver($storage);

    expect($resolver->resolve('centers/1/videos/3/thumbnail/test.png'))->toBe('https://cdn.test/thumb.png');
});
