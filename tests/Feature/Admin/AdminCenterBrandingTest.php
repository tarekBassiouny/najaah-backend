<?php

declare(strict_types=1);

use App\Models\Center;
use App\Services\Storage\StoragePathResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class)->group('centers', 'admin');

beforeEach(function (): void {
    $this->withoutMiddleware();
    $this->asAdmin();
});

it('uploads a center logo and updates the stored path', function (): void {
    Storage::fake('spaces');
    config()->set('filesystems.default', 'spaces');

    $center = Center::factory()->create([
        'logo_url' => 'centers/defaults/logo.png',
    ]);

    $file = UploadedFile::fake()->image('logo.png');
    $response = $this->post(
        "/api/v1/admin/centers/{$center->id}/branding/logo",
        ['logo' => $file],
        $this->adminHeaders()
    );

    $response->assertOk()->assertJsonPath('data.id', $center->id);

    $expectedPath = app(StoragePathResolver::class)->centerLogo($center->id, $file->hashName());
    Storage::disk('spaces')->assertExists($expectedPath);

    $center->refresh();
    expect($center->logo_url)->toBe($expectedPath);
});
