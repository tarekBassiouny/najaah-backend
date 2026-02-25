<?php

declare(strict_types=1);

use App\Services\Courses\CourseThumbnailUrlResolver;
use App\Services\Storage\Contracts\StorageServiceInterface;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

uses(TestCase::class)->group('courses');

afterEach(function (): void {
    \Mockery::close();
});

it('returns null when the thumbnail path is missing', function (): void {
    $storage = \Mockery::mock(StorageServiceInterface::class);
    $resolver = new CourseThumbnailUrlResolver($storage);

    expect($resolver->resolve(null))->toBeNull();
});

it('returns absolute urls without touching storage', function (): void {
    $storage = \Mockery::mock(StorageServiceInterface::class);
    $storage->shouldNotReceive('exists');

    $resolver = new CourseThumbnailUrlResolver($storage);

    expect($resolver->resolve('https://example.com/course-thumb.png'))->toBe('https://example.com/course-thumb.png');
});

it('returns signed urls for private disks', function (): void {
    config()->set('filesystems.default', 'spaces');
    config()->set('filesystems.disks.spaces.visibility', 'private');
    config()->set('filesystems.signed_url_ttl', 600);

    $storage = \Mockery::mock(StorageServiceInterface::class);
    $storage->shouldReceive('exists')->once()->with('centers/1/courses/3/thumbnail/test.png')->andReturn(true);
    $storage->shouldReceive('temporaryUrl')->once()->with('centers/1/courses/3/thumbnail/test.png', 600)->andReturn('https://signed.test/thumb');

    $resolver = new CourseThumbnailUrlResolver($storage);

    expect($resolver->resolve('centers/1/courses/3/thumbnail/test.png'))->toBe('https://signed.test/thumb');
});

it('returns public urls for public disks', function (): void {
    config()->set('filesystems.default', 'public');
    config()->set('filesystems.disks.public.visibility', 'public');

    $storage = \Mockery::mock(StorageServiceInterface::class);
    $storage->shouldReceive('exists')->once()->with('centers/1/courses/3/thumbnail/test.png')->andReturn(true);
    $storage->shouldReceive('url')->once()->with('centers/1/courses/3/thumbnail/test.png')->andReturn('https://cdn.test/thumb.png');

    $resolver = new CourseThumbnailUrlResolver($storage);

    expect($resolver->resolve('centers/1/courses/3/thumbnail/test.png'))->toBe('https://cdn.test/thumb.png');
});

it('returns null and logs warning when thumbnail path is missing on storage', function (): void {
    config()->set('filesystems.default', 'spaces');

    Log::shouldReceive('warning')->once()->with('Course thumbnail path missing on storage.', ['path' => 'centers/1/courses/3/thumbnail/test.png']);

    $storage = \Mockery::mock(StorageServiceInterface::class);
    $storage->shouldReceive('exists')->once()->with('centers/1/courses/3/thumbnail/test.png')->andReturn(false);

    $resolver = new CourseThumbnailUrlResolver($storage);

    expect($resolver->resolve('centers/1/courses/3/thumbnail/test.png'))->toBeNull();
});
