<?php

declare(strict_types=1);

use App\Models\Center;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
