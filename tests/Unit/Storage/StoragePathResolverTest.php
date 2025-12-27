<?php

declare(strict_types=1);

use App\Services\Storage\StoragePathResolver;
use Tests\TestCase;

uses(TestCase::class)->group('storage', 'paths');

it('builds center-scoped paths for common assets', function (): void {
    $resolver = new StoragePathResolver;

    expect($resolver->centerLogo(10, 'logo.png'))->toBe('centers/10/branding/logo/logo.png')
        ->and($resolver->userAvatar(10, 55, 'avatar.jpg'))->toBe('centers/10/users/55/avatar/avatar.jpg')
        ->and($resolver->instructorAvatar(10, 'avatar.jpg'))->toBe('centers/10/instructors/avatars/avatar.jpg')
        ->and($resolver->courseThumbnail(10, 22, 'thumb.jpg'))->toBe('centers/10/courses/22/thumbnail/thumb.jpg')
        ->and($resolver->pdf(10, 'file.pdf'))->toBe('centers/10/pdfs/file.pdf')
        ->and($resolver->export(10, 'export.csv'))->toBe('centers/10/exports/export.csv');
});
