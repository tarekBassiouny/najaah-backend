<?php

declare(strict_types=1);

use App\Models\Course;
use App\Models\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class)->group('pdfs', 'admin');

it('allows admin to upload a pdf to private storage', function (): void {
    Storage::fake('local');

    $admin = $this->asAdmin();
    $course = Course::factory()->create();

    $file = UploadedFile::fake()->create('document.pdf', 500, 'application/pdf');

    $response = $this->actingAs($admin, 'admin')->postJson('/api/v1/admin/pdfs', [
        'title_translations' => ['en' => 'Doc'],
        'description_translations' => ['en' => 'Sample'],
        'file' => $file,
        'course_id' => $course->id,
    ], $this->adminHeaders());

    $response->assertCreated()->assertJsonPath('data.title', 'Doc');

    $pdf = Pdf::first();
    expect($pdf)->not->toBeNull()
        ->and($pdf?->source_type)->toBe(1)
        ->and($pdf?->source_provider)->toBe('local');

    Storage::disk('local')->assertExists($pdf->source_id);
});
