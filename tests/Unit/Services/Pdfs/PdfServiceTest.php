<?php

declare(strict_types=1);

use App\Enums\PdfUploadStatus;
use App\Models\Center;
use App\Models\Pdf;
use App\Models\PdfUploadSession;
use App\Services\Pdfs\PdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\AdminTestHelper;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class, AdminTestHelper::class)->group('pdfs', 'services');

it('creates pdf from a ready upload session', function (): void {
    $admin = $this->asAdmin();
    $center = Center::factory()->create();
    $session = PdfUploadSession::factory()->create([
        'center_id' => $center->id,
        'upload_status' => PdfUploadStatus::Ready,
        'object_key' => 'centers/'.$center->id.'/pdfs/lesson.pdf',
        'file_extension' => 'pdf',
        'file_size_kb' => 2048,
    ]);

    $service = app(PdfService::class);
    $pdf = $service->create($center, $admin, [
        'title_translations' => ['en' => 'PDF Lesson'],
        'description_translations' => ['en' => 'Intro'],
        'upload_session_id' => $session->id,
    ]);

    expect((int) $pdf->center_id)->toBe($center->id);
    expect((int) $pdf->upload_session_id)->toBe($session->id);
    expect($pdf->source_id)->toBe($session->object_key);
});

it('updates and deletes pdf', function (): void {
    $admin = $this->asAdmin();
    $center = Center::factory()->create();
    $pdf = Pdf::factory()->create([
        'center_id' => $center->id,
        'created_by' => $admin->id,
    ]);

    $service = app(PdfService::class);
    $updated = $service->update($pdf, $admin, [
        'title_translations' => ['en' => 'Updated Title'],
    ]);
    expect(data_get($updated->title_translations, 'en'))->toBe('Updated Title');

    $service->delete($updated, $admin);
    expect($updated->fresh()?->deleted_at)->not()->toBeNull();
});
