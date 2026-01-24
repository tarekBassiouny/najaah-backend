<?php

declare(strict_types=1);

use App\Enums\PdfUploadStatus;
use App\Models\Pdf;
use App\Models\PdfUploadSession;
use App\Services\Storage\Contracts\StorageServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('pdfs', 'admin');

afterEach(function (): void {
    \Mockery::close();
});

it('creates pdf from upload session', function (): void {
    $center = \App\Models\Center::factory()->create();
    $admin = $this->asAdmin();
    $admin->update(['center_id' => $center->id]);

    $storage = \Mockery::mock(StorageServiceInterface::class);
    $storage->shouldReceive('temporaryUploadUrl')->once()->andReturn('https://signed.test/upload');
    $this->app->instance(StorageServiceInterface::class, $storage);

    $sessionResponse = $this->actingAs($admin, 'admin')->postJson(
        "/api/v1/admin/centers/{$center->id}/pdfs/upload-sessions",
        [
            'original_filename' => 'document.pdf',
            'file_size_kb' => 500,
        ],
        $this->adminHeaders()
    );

    $sessionResponse->assertCreated()
        ->assertJsonPath('data.provider', 'spaces')
        ->assertJsonPath('data.upload_endpoint', 'https://signed.test/upload');

    /** @var PdfUploadSession $session */
    $session = PdfUploadSession::latest()->firstOrFail();

    $response = $this->actingAs($admin, 'admin')->postJson(
        "/api/v1/admin/centers/{$center->id}/pdfs",
        [
            'title' => 'Doc',
            'description' => 'Sample',
            'upload_session_id' => $session->id,
        ],
        $this->adminHeaders()
    );

    $response->assertCreated()->assertJsonPath('data.title', 'Doc');

    $pdf = Pdf::first();
    expect($pdf)->not->toBeNull()
        ->and($pdf?->source_type)->toBe(1)
        ->and($pdf?->source_provider)->toBe('spaces')
        ->and($pdf?->source_id)->toBe($session->object_key);
});

it('fails finalize when uploaded object is missing', function (): void {
    $center = \App\Models\Center::factory()->create();
    $admin = $this->asAdmin();
    $admin->update(['center_id' => $center->id]);
    $session = PdfUploadSession::factory()->create([
        'center_id' => $center->id,
        'created_by' => $admin->id,
        'upload_status' => PdfUploadStatus::Pending,
    ]);

    $storage = \Mockery::mock(StorageServiceInterface::class);
    $storage->shouldReceive('exists')->once()->with($session->object_key)->andReturn(false);
    $this->app->instance(StorageServiceInterface::class, $storage);

    $response = $this->actingAs($admin, 'admin')->postJson(
        "/api/v1/admin/centers/{$center->id}/pdfs/upload-sessions/{$session->id}/finalize",
        ['title' => 'Doc'],
        $this->adminHeaders()
    );

    $response->assertStatus(422);

    $session->refresh();
    expect($session->upload_status)->toBe(PdfUploadStatus::Failed);
});

it('finalizes upload session and creates pdf when object exists', function (): void {
    $center = \App\Models\Center::factory()->create();
    $admin = $this->asAdmin();
    $admin->update(['center_id' => $center->id]);
    $session = PdfUploadSession::factory()->create([
        'center_id' => $center->id,
        'created_by' => $admin->id,
        'upload_status' => PdfUploadStatus::Pending,
    ]);

    $storage = \Mockery::mock(StorageServiceInterface::class);
    $storage->shouldReceive('exists')->once()->with($session->object_key)->andReturn(true);
    $this->app->instance(StorageServiceInterface::class, $storage);

    $response = $this->actingAs($admin, 'admin')->postJson(
        "/api/v1/admin/centers/{$center->id}/pdfs/upload-sessions/{$session->id}/finalize",
        ['title' => 'Doc'],
        $this->adminHeaders()
    );

    $response->assertOk()->assertJsonPath('data.upload_status', PdfUploadStatus::Ready->value);

    $pdf = Pdf::first();
    expect($pdf)->not->toBeNull()
        ->and($pdf?->upload_session_id)->toBe($session->id)
        ->and($pdf?->source_id)->toBe($session->object_key);
});
