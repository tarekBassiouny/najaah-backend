<?php

declare(strict_types=1);

use App\Services\Branding\CenterLogoUrlResolver;
use App\Services\Storage\Contracts\StorageServiceInterface;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

uses(TestCase::class)->group('branding');

afterEach(function (): void {
    \Mockery::close();
});

it('returns null when the logo path is missing', function (): void {
    $storage = \Mockery::mock(StorageServiceInterface::class);
    $resolver = new CenterLogoUrlResolver($storage);

    expect($resolver->resolve(null))->toBeNull();
});

it('returns absolute urls without touching storage', function (): void {
    $storage = \Mockery::mock(StorageServiceInterface::class);
    $storage->shouldNotReceive('exists');

    $resolver = new CenterLogoUrlResolver($storage);

    expect($resolver->resolve('https://example.com/logo.png'))->toBe('https://example.com/logo.png');
});

it('returns signed urls for private disks', function (): void {
    config()->set('filesystems.default', 'spaces');
    config()->set('filesystems.disks.spaces.visibility', 'private');
    config()->set('filesystems.signed_url_ttl', 600);

    $storage = \Mockery::mock(StorageServiceInterface::class);
    $storage->shouldReceive('exists')->once()->with('centers/1/logo.png')->andReturn(true);
    $storage->shouldReceive('temporaryUrl')->once()->with('centers/1/logo.png', 600)->andReturn('https://signed.test/logo');

    $resolver = new CenterLogoUrlResolver($storage);

    expect($resolver->resolve('centers/1/logo.png'))->toBe('https://signed.test/logo');
});

it('returns public urls for public disks', function (): void {
    config()->set('filesystems.default', 'public');
    config()->set('filesystems.disks.public.visibility', 'public');

    $storage = \Mockery::mock(StorageServiceInterface::class);
    $storage->shouldReceive('exists')->once()->with('centers/1/logo.png')->andReturn(true);
    $storage->shouldReceive('url')->once()->with('centers/1/logo.png')->andReturn('https://cdn.test/logo.png');

    $resolver = new CenterLogoUrlResolver($storage);

    expect($resolver->resolve('centers/1/logo.png'))->toBe('https://cdn.test/logo.png');
});

it('returns null and logs warning when the logo path is missing on storage', function (): void {
    config()->set('filesystems.default', 'spaces');

    Log::shouldReceive('warning')->once()->with('Center logo path missing on storage.', ['path' => 'centers/1/logo.png']);

    $storage = \Mockery::mock(StorageServiceInterface::class);
    $storage->shouldReceive('exists')->once()->with('centers/1/logo.png')->andReturn(false);

    $resolver = new CenterLogoUrlResolver($storage);

    expect($resolver->resolve('centers/1/logo.png'))->toBeNull();
});
