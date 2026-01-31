<?php

declare(strict_types=1);

use App\Enums\VideoLifecycleStatus;
use App\Enums\VideoUploadStatus;
use App\Exceptions\AttachmentNotAllowedException;
use App\Models\Center;
use App\Models\Course;
use App\Models\Section;
use App\Models\Video;
use App\Services\Sections\SectionStructureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\AdminTestHelper;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class, AdminTestHelper::class)->group('sections', 'services');

it('blocks attaching videos to deleted sections', function (): void {
    $admin = $this->asAdmin();
    $center = Center::factory()->create();
    $course = Course::factory()->create(['center_id' => $center->id, 'created_by' => $admin->id]);
    $section = Section::factory()->create(['course_id' => $course->id]);
    $section->delete();

    $video = Video::factory()->create([
        'center_id' => $center->id,
        'encoding_status' => VideoUploadStatus::Ready,
        'lifecycle_status' => VideoLifecycleStatus::Ready,
        'created_by' => $admin->id,
    ]);

    $service = app(SectionStructureService::class);

    $service->attachVideo($section, $video, $admin);
})->throws(AttachmentNotAllowedException::class);
