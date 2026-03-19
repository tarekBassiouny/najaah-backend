<?php

declare(strict_types=1);

use App\Enums\TranscriptFormat;
use App\Enums\TranscriptSource;
use App\Models\Center;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\Helpers\AdminTestHelper;

uses(RefreshDatabase::class, AdminTestHelper::class)->group('videos', 'admin');

it('stores transcript text for a video', function (): void {
    $center = Center::factory()->create();
    $admin = $this->asCenterAdmin($center);
    $video = Video::factory()->create([
        'center_id' => $center->id,
        'created_by' => $admin->id,
    ]);

    $response = $this->postJson(
        "/api/v1/admin/centers/{$center->id}/videos/{$video->id}/transcript",
        [
            'transcript_text' => "Intro line.\n\nSecond line.",
        ],
        $this->adminHeaders()
    );

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.video_id', $video->id)
        ->assertJsonPath('data.has_transcript', true)
        ->assertJsonPath('data.transcript', "Intro line.\n\nSecond line.")
        ->assertJsonPath('data.transcript_format', TranscriptFormat::Txt->value)
        ->assertJsonPath('data.transcript_source', TranscriptSource::Manual->value);

    $video->refresh();
    expect($video->transcript)->toBe("Intro line.\n\nSecond line.")
        ->and($video->transcript_format)->toBe(TranscriptFormat::Txt)
        ->and($video->transcript_source)->toBe(TranscriptSource::Manual);
});

it('stores transcript from srt upload for a video', function (): void {
    $center = Center::factory()->create();
    $admin = $this->asCenterAdmin($center);
    $video = Video::factory()->create([
        'center_id' => $center->id,
        'created_by' => $admin->id,
    ]);

    $file = UploadedFile::fake()->createWithContent('lesson.srt', implode("\n", [
        '1',
        '00:00:00,000 --> 00:00:02,000',
        'Welcome to algebra.',
        '',
        '2',
        '00:00:02,500 --> 00:00:04,000',
        'We start with variables.',
    ]));

    $response = $this->post(
        "/api/v1/admin/centers/{$center->id}/videos/{$video->id}/transcript",
        ['file' => $file],
        $this->adminHeaders()
    );

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.transcript', "Welcome to algebra.\nWe start with variables.")
        ->assertJsonPath('data.transcript_format', TranscriptFormat::Srt->value)
        ->assertJsonPath('data.transcript_source', TranscriptSource::Manual->value);
});

it('deletes transcript for a video', function (): void {
    $center = Center::factory()->create();
    $admin = $this->asCenterAdmin($center);
    $video = Video::factory()->create([
        'center_id' => $center->id,
        'created_by' => $admin->id,
        'transcript' => 'Existing transcript',
        'transcript_format' => TranscriptFormat::Txt,
        'transcript_source' => TranscriptSource::Manual,
    ]);

    $response = $this->deleteJson(
        "/api/v1/admin/centers/{$center->id}/videos/{$video->id}/transcript",
        [],
        $this->adminHeaders()
    );

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.has_transcript', false)
        ->assertJsonPath('data.transcript', null)
        ->assertJsonPath('data.transcript_format', null)
        ->assertJsonPath('data.transcript_source', null);

    $video->refresh();
    expect($video->transcript)->toBeNull()
        ->and($video->transcript_format)->toBeNull()
        ->and($video->transcript_source)->toBeNull();
});

it('returns not found for transcript routes when video center mismatches', function (): void {
    $centerA = Center::factory()->create();
    $centerB = Center::factory()->create();
    $admin = $this->asCenterAdmin($centerA);
    $video = Video::factory()->create([
        'center_id' => $centerB->id,
        'created_by' => $admin->id,
    ]);

    $response = $this->getJson(
        "/api/v1/admin/centers/{$centerA->id}/videos/{$video->id}/transcript",
        $this->adminHeaders()
    );

    $response->assertNotFound()
        ->assertJsonPath('error.code', 'NOT_FOUND');
});
