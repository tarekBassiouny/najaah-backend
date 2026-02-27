<?php

declare(strict_types=1);

use App\Enums\MediaSourceType;
use App\Enums\PdfUploadStatus;
use App\Models\Center;
use App\Models\Course;
use App\Models\Pdf;
use App\Models\PdfUploadSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\AdminTestHelper;

uses(RefreshDatabase::class, AdminTestHelper::class)->group('pdfs', 'admin');

it('lists pdfs for admin center', function (): void {
    $center = Center::factory()->create();
    $otherCenter = Center::factory()->create();
    $admin = $this->asCenterAdmin($center);

    $pdf = Pdf::factory()->create([
        'center_id' => $center->id,
        'created_by' => $admin->id,
        'title_translations' => ['en' => 'Center PDF'],
    ]);

    Pdf::factory()->create([
        'center_id' => $otherCenter->id,
        'title_translations' => ['en' => 'Other PDF'],
    ]);

    $response = $this->getJson("/api/v1/admin/centers/{$center->id}/pdfs", $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.id', $pdf->id)
        ->assertJsonPath('data.0.title', 'Center PDF');
});

it('filters pdfs by course id', function (): void {
    $center = Center::factory()->create();
    $admin = $this->asCenterAdmin($center);

    $courseA = Course::factory()->create(['center_id' => $center->id]);
    $courseB = Course::factory()->create(['center_id' => $center->id]);

    $pdfA = Pdf::factory()->create([
        'center_id' => $center->id,
        'created_by' => $admin->id,
        'title_translations' => ['en' => 'Course A PDF'],
    ]);
    $pdfB = Pdf::factory()->create([
        'center_id' => $center->id,
        'created_by' => $admin->id,
        'title_translations' => ['en' => 'Course B PDF'],
    ]);

    $courseA->pdfs()->attach($pdfA->id, ['section_id' => null, 'video_id' => null]);
    $courseB->pdfs()->attach($pdfB->id, ['section_id' => null, 'video_id' => null]);

    $response = $this->getJson("/api/v1/admin/centers/{$center->id}/pdfs?course_id={$courseA->id}", $this->adminHeaders());

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $pdfA->id);
});

it('filters pdfs by legacy title search', function (): void {
    $center = Center::factory()->create();
    $admin = $this->asCenterAdmin($center);

    Pdf::factory()->create([
        'center_id' => $center->id,
        'created_by' => $admin->id,
        'title_translations' => ['en' => 'Lesson Notes'],
    ]);

    Pdf::factory()->create([
        'center_id' => $center->id,
        'created_by' => $admin->id,
        'title_translations' => ['en' => 'Homework'],
    ]);

    $response = $this->getJson("/api/v1/admin/centers/{$center->id}/pdfs?search=Lesson", $this->adminHeaders());

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Lesson Notes');
});

it('filters pdfs by status source type and provider', function (): void {
    $center = Center::factory()->create();
    $admin = $this->asCenterAdmin($center);

    $readySession = PdfUploadSession::factory()->create([
        'center_id' => $center->id,
        'created_by' => $admin->id,
        'upload_status' => PdfUploadStatus::Ready,
    ]);

    $uploadingSession = PdfUploadSession::factory()->create([
        'center_id' => $center->id,
        'created_by' => $admin->id,
        'upload_status' => PdfUploadStatus::Uploading,
    ]);

    $match = Pdf::factory()->create([
        'center_id' => $center->id,
        'created_by' => $admin->id,
        'upload_session_id' => $readySession->id,
        'source_type' => MediaSourceType::Upload,
        'source_provider' => 'spaces',
        'title_translations' => ['en' => 'Matched PDF'],
    ]);

    Pdf::factory()->create([
        'center_id' => $center->id,
        'created_by' => $admin->id,
        'upload_session_id' => $uploadingSession->id,
        'source_type' => MediaSourceType::Upload,
        'source_provider' => 'spaces',
        'title_translations' => ['en' => 'Wrong status'],
    ]);

    Pdf::factory()->create([
        'center_id' => $center->id,
        'created_by' => $admin->id,
        'upload_session_id' => $readySession->id,
        'source_type' => MediaSourceType::Url,
        'source_provider' => 'custom',
        'title_translations' => ['en' => 'Wrong type/provider'],
    ]);

    $response = $this->getJson(
        "/api/v1/admin/centers/{$center->id}/pdfs?status=ready&source_type=upload&source_provider=spaces",
        $this->adminHeaders()
    );

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $match->id);
});

it('filters pdfs by created_at date range', function (): void {
    $center = Center::factory()->create();
    $admin = $this->asCenterAdmin($center);

    Pdf::factory()->create([
        'center_id' => $center->id,
        'created_by' => $admin->id,
        'title_translations' => ['en' => 'Old PDF'],
        'created_at' => '2026-01-10 10:00:00',
    ]);

    $newPdf = Pdf::factory()->create([
        'center_id' => $center->id,
        'created_by' => $admin->id,
        'title_translations' => ['en' => 'New PDF'],
        'created_at' => '2026-02-20 10:00:00',
    ]);

    $response = $this->getJson(
        "/api/v1/admin/centers/{$center->id}/pdfs?created_from=2026-02-01&created_to=2026-02-28",
        $this->adminHeaders()
    );

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $newPdf->id);
});

it('supports unified q search across title description and source id', function (): void {
    $center = Center::factory()->create();
    $admin = $this->asCenterAdmin($center);

    $titleMatch = Pdf::factory()->create([
        'center_id' => $center->id,
        'created_by' => $admin->id,
        'title_translations' => ['en' => 'Algebra Notes'],
        'description_translations' => ['en' => 'Lesson material'],
        'source_id' => 'centers/4/pdfs/algebra-notes.pdf',
    ]);

    $descriptionMatch = Pdf::factory()->create([
        'center_id' => $center->id,
        'created_by' => $admin->id,
        'title_translations' => ['en' => 'Physics'],
        'description_translations' => ['en' => 'exam guide'],
        'source_id' => 'centers/4/pdfs/physics.pdf',
    ]);

    $sourceIdMatch = Pdf::factory()->create([
        'center_id' => $center->id,
        'created_by' => $admin->id,
        'title_translations' => ['en' => 'History'],
        'description_translations' => ['en' => 'Archive'],
        'source_id' => 'centers/4/pdfs/special-handout.pdf',
    ]);

    $titleResponse = $this->getJson("/api/v1/admin/centers/{$center->id}/pdfs?q=Algebra", $this->adminHeaders());
    $titleResponse->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $titleMatch->id);

    $descriptionResponse = $this->getJson("/api/v1/admin/centers/{$center->id}/pdfs?q=exam", $this->adminHeaders());
    $descriptionResponse->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $descriptionMatch->id);

    $sourceIdResponse = $this->getJson("/api/v1/admin/centers/{$center->id}/pdfs?q=handout", $this->adminHeaders());
    $sourceIdResponse->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $sourceIdMatch->id);
});

it('prioritizes q over legacy search', function (): void {
    $center = Center::factory()->create();
    $admin = $this->asCenterAdmin($center);

    $qMatch = Pdf::factory()->create([
        'center_id' => $center->id,
        'created_by' => $admin->id,
        'title_translations' => ['en' => 'Any title'],
        'description_translations' => ['en' => 'target term'],
    ]);

    Pdf::factory()->create([
        'center_id' => $center->id,
        'created_by' => $admin->id,
        'title_translations' => ['en' => 'legacy-only'],
        'description_translations' => ['en' => 'not matching'],
    ]);

    $response = $this->getJson(
        "/api/v1/admin/centers/{$center->id}/pdfs?search=legacy&q=target",
        $this->adminHeaders()
    );

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $qMatch->id);
});
