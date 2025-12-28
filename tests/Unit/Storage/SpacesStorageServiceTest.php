<?php

declare(strict_types=1);

use App\Services\Storage\SpacesStorageService;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Tests\TestCase;

uses(TestCase::class)->group('storage', 'spaces');

afterEach(function (): void {
    Carbon::setTestNow();
    \Mockery::close();
});

it('uploads files using the storage disk with private visibility', function (): void {
    $disk = \Mockery::mock(Filesystem::class);
    $file = UploadedFile::fake()->image('avatar.jpg');

    $disk->shouldReceive('putFileAs')
        ->once()
        ->with('centers/9/instructors/avatars', $file, 'avatar.jpg', ['visibility' => 'private'])
        ->andReturn('centers/9/instructors/avatars/avatar.jpg');

    $service = new SpacesStorageService($disk);

    $stored = $service->upload('centers/9/instructors/avatars/avatar.jpg', $file);

    expect($stored)->toBe('centers/9/instructors/avatars/avatar.jpg');
});

it('builds signed urls with the requested expiration', function (): void {
    Carbon::setTestNow('2024-01-01 00:00:00');
    $expiresAt = now()->addSeconds(600)->timestamp;

    $disk = \Mockery::mock(Filesystem::class);
    $disk->shouldReceive('temporaryUrl')
        ->once()
        ->with('centers/1/avatars/avatar.jpg', \Mockery::on(function ($date) use ($expiresAt): bool {
            return $date instanceof DateTimeInterface && $date->getTimestamp() === $expiresAt;
        }))
        ->andReturn('https://signed.test/url');

    $service = new SpacesStorageService($disk);

    $url = $service->temporaryUrl('centers/1/avatars/avatar.jpg', 600);

    expect($url)->toBe('https://signed.test/url');
});

it('checks file existence via the storage disk', function (): void {
    $disk = \Mockery::mock(Filesystem::class);
    $disk->shouldReceive('exists')->once()->with('centers/1/file.pdf')->andReturn(true);

    $service = new SpacesStorageService($disk);

    expect($service->exists('centers/1/file.pdf'))->toBeTrue();
});

it('returns public urls using the storage disk', function (): void {
    $disk = \Mockery::mock(Filesystem::class);
    $disk->shouldReceive('url')->once()->with('centers/1/file.pdf')->andReturn('https://cdn.test/file.pdf');

    $service = new SpacesStorageService($disk);

    expect($service->url('centers/1/file.pdf'))->toBe('https://cdn.test/file.pdf');
});
