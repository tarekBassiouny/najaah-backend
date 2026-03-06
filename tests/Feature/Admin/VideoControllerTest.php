<?php

declare(strict_types=1);

use App\Models\Center;
use App\Models\Video;
use App\Services\Storage\StoragePathResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Helpers\AdminTestHelper;

uses(RefreshDatabase::class, AdminTestHelper::class)->group('videos', 'admin');

it('rejects array title payload when creating video', function (): void {
    $center = Center::factory()->create();
    $this->asCenterAdmin($center);

    $response = $this->postJson(
        "/api/v1/admin/centers/{$center->id}/videos",
        [
            'title' => ['en' => 'Bad'],
            'description' => 'Sample description',
        ]
    );

    $response->assertStatus(422);
});

it('creates a url source video through regular store endpoint', function (): void {
    $center = Center::factory()->create();
    $this->asCenterAdmin($center);

    $response = $this->postJson(
        "/api/v1/admin/centers/{$center->id}/videos",
        [
            'source_type' => 'url',
            'source_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'title_translations' => ['en' => 'YouTube Lesson'],
            'description_translations' => ['en' => 'External source'],
        ],
        $this->adminHeaders()
    );

    $response->assertCreated()
        ->assertJsonPath('data.source_url', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ')
        ->assertJsonPath('data.source_provider', 'youtube')
        ->assertJsonPath('data.encoding_status_key', 'ready')
        ->assertJsonPath('data.lifecycle_status_key', 'ready');
});

it('uploads a custom thumbnail via dedicated endpoint', function (): void {
    Storage::fake('spaces');
    config()->set('filesystems.default', 'spaces');

    $center = Center::factory()->create();
    $admin = $this->asCenterAdmin($center);
    $oldPath = "centers/{$center->id}/videos/legacy/thumbnail/old-thumb.jpg";
    Storage::disk('spaces')->put($oldPath, 'old-thumb');

    $video = Video::factory()->create([
        'center_id' => $center->id,
        'created_by' => $admin->id,
        'thumbnail_url' => 'https://cdn.example.com/default-thumb.jpg',
        'custom_thumbnail_url' => $oldPath,
    ]);

    $file = UploadedFile::fake()->image('custom-thumb.jpg');

    $response = $this->post(
        "/api/v1/admin/centers/{$center->id}/videos/{$video->id}/thumbnail",
        ['thumbnail' => $file],
        $this->adminHeaders()
    );

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $video->id)
        ->assertJsonPath('data.has_custom_thumbnail', true)
        ->assertJsonPath('data.custom_thumbnail_url', fn ($value) => is_string($value) && $value !== '')
        ->assertJsonPath('data.thumbnail_url', fn ($value) => is_string($value) && $value !== '');

    $expectedPath = app(StoragePathResolver::class)->videoThumbnail($center->id, $video->id, $file->hashName());
    Storage::disk('spaces')->assertExists($expectedPath);
    Storage::disk('spaces')->assertMissing($oldPath);

    $video->refresh();
    expect($video->custom_thumbnail_url)->toBe($expectedPath);
});

it('rejects oversized custom thumbnail upload', function (): void {
    Storage::fake('spaces');
    config()->set('filesystems.default', 'spaces');

    $center = Center::factory()->create();
    $admin = $this->asCenterAdmin($center);
    $video = Video::factory()->create([
        'center_id' => $center->id,
        'created_by' => $admin->id,
    ]);

    $file = UploadedFile::fake()->image('huge-thumb.jpg')->size(6000);

    $response = $this->post(
        "/api/v1/admin/centers/{$center->id}/videos/{$video->id}/thumbnail",
        ['thumbnail' => $file],
        $this->adminHeaders()
    );

    $response->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonPath('error.code', 'VALIDATION_ERROR')
        ->assertJsonPath('error.details.thumbnail.0', fn ($value) => is_string($value) && $value !== '');
});

it('clears custom thumbnail and falls back to default thumbnail', function (): void {
    Storage::fake('spaces');
    config()->set('filesystems.default', 'spaces');

    $center = Center::factory()->create();
    $admin = $this->asCenterAdmin($center);
    $oldPath = "centers/{$center->id}/videos/123/thumbnail/custom-thumb.jpg";
    Storage::disk('spaces')->put($oldPath, 'custom-thumb');

    $video = Video::factory()->create([
        'center_id' => $center->id,
        'created_by' => $admin->id,
        'thumbnail_url' => 'https://cdn.example.com/default-thumb.jpg',
        'custom_thumbnail_url' => $oldPath,
    ]);

    $response = $this->deleteJson(
        "/api/v1/admin/centers/{$center->id}/videos/{$video->id}/thumbnail",
        [],
        $this->adminHeaders()
    );

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $video->id)
        ->assertJsonPath('data.has_custom_thumbnail', false)
        ->assertJsonPath('data.custom_thumbnail_url', null)
        ->assertJsonPath('data.thumbnail_url', 'https://cdn.example.com/default-thumb.jpg');

    $video->refresh();
    expect($video->custom_thumbnail_url)->toBeNull();
    Storage::disk('spaces')->assertMissing($oldPath);
});

it('returns signed preview URL for ready video in same center scope', function (): void {
    config(['bunny.embed_key' => 'test-secret', 'bunny.api.library_id' => 123]);

    $center = Center::factory()->create();
    $admin = $this->asCenterAdmin($center);

    $video = Video::factory()->create([
        'center_id' => $center->id,
        'created_by' => $admin->id,
        'source_id' => 'video-guid-123',
        'library_id' => 123,
    ]);

    $response = $this->postJson(
        "/api/v1/admin/centers/{$center->id}/videos/{$video->id}/preview",
        [],
        $this->adminHeaders()
    );

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.embed_url', fn ($value) => is_string($value) && str_contains($value, 'iframe.mediadelivery.net/embed/123/video-guid-123'))
        ->assertJsonPath('data.expires', fn ($value) => is_int($value) || is_numeric($value))
        ->assertJsonPath('data.expires_at', fn ($value) => is_string($value) && $value !== '');
});

it('rejects preview for non-ready video without source id', function (): void {
    config(['bunny.embed_key' => 'test-secret']);

    $center = Center::factory()->create();
    $admin = $this->asCenterAdmin($center);

    $video = Video::factory()->create([
        'center_id' => $center->id,
        'created_by' => $admin->id,
        'source_id' => null,
    ]);

    $response = $this->postJson(
        "/api/v1/admin/centers/{$center->id}/videos/{$video->id}/preview",
        [],
        $this->adminHeaders()
    );

    $response->assertStatus(422)
        ->assertJsonPath('error.code', 'VIDEO_NOT_READY');
});

it('returns normalized embed url when previewing a youtube url-source video', function (): void {
    $center = Center::factory()->create();
    $admin = $this->asCenterAdmin($center);

    $video = Video::factory()->create([
        'center_id' => $center->id,
        'created_by' => $admin->id,
        'source_type' => 0,
        'source_provider' => 'youtube',
        'source_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        'source_id' => null,
    ]);

    $response = $this->postJson(
        "/api/v1/admin/centers/{$center->id}/videos/{$video->id}/preview",
        [],
        $this->adminHeaders()
    );

    $response->assertOk()
        ->assertJsonPath('data.embed_url', 'https://www.youtube.com/embed/dQw4w9WgXcQ')
        ->assertJsonPath('data.expires_at', null)
        ->assertJsonPath('data.expires', null);
});

it('returns normalized embed url when previewing a vimeo url-source video', function (): void {
    $center = Center::factory()->create();
    $admin = $this->asCenterAdmin($center);

    $video = Video::factory()->create([
        'center_id' => $center->id,
        'created_by' => $admin->id,
        'source_type' => 0,
        'source_provider' => 'vimeo',
        'source_url' => 'https://vimeo.com/123456789',
        'source_id' => null,
    ]);

    $response = $this->postJson(
        "/api/v1/admin/centers/{$center->id}/videos/{$video->id}/preview",
        [],
        $this->adminHeaders()
    );

    $response->assertOk()
        ->assertJsonPath('data.embed_url', 'https://player.vimeo.com/video/123456789')
        ->assertJsonPath('data.expires_at', null)
        ->assertJsonPath('data.expires', null);
});
