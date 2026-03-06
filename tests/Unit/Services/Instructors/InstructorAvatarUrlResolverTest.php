<?php

declare(strict_types=1);

use App\Services\Instructors\InstructorAvatarUrlResolver;
use App\Services\Storage\Contracts\StorageServiceInterface;
use Tests\TestCase;

uses(TestCase::class)->group('instructors');

afterEach(function (): void {
    \Mockery::close();
});

it('returns null when the avatar path is missing', function (): void {
    $storage = \Mockery::mock(StorageServiceInterface::class);
    $resolver = new InstructorAvatarUrlResolver($storage);

    expect($resolver->resolve(null))->toBeNull();
});

it('returns absolute urls without touching storage', function (): void {
    $storage = \Mockery::mock(StorageServiceInterface::class);
    $storage->shouldNotReceive('exists');
    $storage->shouldNotReceive('temporaryUrl');
    $storage->shouldNotReceive('url');

    $resolver = new InstructorAvatarUrlResolver($storage);

    expect($resolver->resolve('https://example.com/avatar.png'))->toBe('https://example.com/avatar.png');
});

it('returns signed urls for private disks', function (): void {
    config()->set('filesystems.default', 'spaces');
    config()->set('filesystems.disks.spaces.visibility', 'private');
    config()->set('filesystems.signed_url_ttl', 600);

    $storage = \Mockery::mock(StorageServiceInterface::class);
    $storage->shouldNotReceive('exists');
    $storage->shouldReceive('temporaryUrl')->once()->with('centers/1/instructors/avatars/test.png', 600)->andReturn('https://signed.test/avatar');

    $resolver = new InstructorAvatarUrlResolver($storage);

    expect($resolver->resolve('centers/1/instructors/avatars/test.png'))->toBe('https://signed.test/avatar');
});

it('returns public urls for public disks', function (): void {
    config()->set('filesystems.default', 'public');
    config()->set('filesystems.disks.public.visibility', 'public');

    $storage = \Mockery::mock(StorageServiceInterface::class);
    $storage->shouldNotReceive('exists');
    $storage->shouldReceive('url')->once()->with('centers/1/instructors/avatars/test.png')->andReturn('https://cdn.test/avatar.png');

    $resolver = new InstructorAvatarUrlResolver($storage);

    expect($resolver->resolve('centers/1/instructors/avatars/test.png'))->toBe('https://cdn.test/avatar.png');
});
