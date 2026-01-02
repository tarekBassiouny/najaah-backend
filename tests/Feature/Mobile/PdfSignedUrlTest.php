<?php

declare(strict_types=1);

use App\Models\Center;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Pdf;
use App\Models\Pivots\CoursePdf;
use App\Models\Section;
use App\Models\User;
use App\Services\Storage\Contracts\StorageServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('pdfs', 'mobile');

beforeEach(function (): void {
    config(['pdf.signed_url_ttl' => 300]);
});

afterEach(function (): void {
    Mockery::close();
});

it('returns signed url for enrolled students', function (): void {
    $center = Center::factory()->create(['pdf_download_permission' => true]);
    $creator = User::factory()->create(['center_id' => $center->id, 'is_student' => false]);
    $student = User::factory()->create([
        'center_id' => $center->id,
        'is_student' => true,
        'password' => 'secret123',
    ]);

    $course = Course::factory()->create([
        'center_id' => $center->id,
        'created_by' => $creator->id,
        'status' => 3,
        'is_published' => true,
    ]);
    $section = Section::factory()->create(['course_id' => $course->id, 'order_index' => 1]);

    $path = 'centers/'.$center->id.'/pdfs/demo.pdf';
    $pdf = Pdf::factory()->create([
        'created_by' => $creator->id,
        'source_id' => $path,
        'source_url' => null,
    ]);

    CoursePdf::create([
        'course_id' => $course->id,
        'pdf_id' => $pdf->id,
        'section_id' => $section->id,
        'order_index' => 1,
        'visible' => true,
    ]);

    Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'center_id' => $center->id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    $storage = Mockery::mock(StorageServiceInterface::class);
    $storage->shouldReceive('exists')->once()->with($path)->andReturn(true);
    $storage->shouldReceive('temporaryUrl')->once()->with($path, 300)->andReturn('https://signed.test/pdf');
    $this->app->instance(StorageServiceInterface::class, $storage);

    $this->asApiUser($student);

    $response = $this->apiGet("/api/v1/centers/{$center->id}/courses/{$course->id}/pdfs/{$pdf->id}/signed-url");

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.url', 'https://signed.test/pdf')
        ->assertJsonPath('data.expires_in', 300);
});

it('blocks signed url for hidden pdfs', function (): void {
    $center = Center::factory()->create(['pdf_download_permission' => true]);
    $creator = User::factory()->create(['center_id' => $center->id, 'is_student' => false]);
    $student = User::factory()->create([
        'center_id' => $center->id,
        'is_student' => true,
        'password' => 'secret123',
    ]);

    $course = Course::factory()->create([
        'center_id' => $center->id,
        'created_by' => $creator->id,
        'status' => 3,
        'is_published' => true,
    ]);
    $section = Section::factory()->create(['course_id' => $course->id, 'order_index' => 1]);

    $pdf = Pdf::factory()->create([
        'created_by' => $creator->id,
        'source_id' => 'centers/'.$center->id.'/pdfs/hidden.pdf',
        'source_url' => null,
    ]);

    CoursePdf::create([
        'course_id' => $course->id,
        'pdf_id' => $pdf->id,
        'section_id' => $section->id,
        'order_index' => 1,
        'visible' => false,
    ]);

    Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'center_id' => $center->id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    $this->asApiUser($student);

    $response = $this->apiGet("/api/v1/centers/{$center->id}/courses/{$course->id}/pdfs/{$pdf->id}/signed-url");

    $response->assertStatus(403);
});
