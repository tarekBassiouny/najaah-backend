<?php

declare(strict_types=1);

use App\Enums\VideoUploadStatus;
use App\Filters\Admin\CategoryFilters;
use App\Filters\Admin\PdfFilters;
use App\Filters\Admin\VideoUploadSessionFilters;
use App\Models\Category;
use App\Models\Center;
use App\Models\Course;
use App\Models\Pdf;
use App\Models\VideoUploadSession;
use App\Services\Categories\AdminCategoryQueryService;
use App\Services\Pdfs\AdminPdfQueryService;
use App\Services\Videos\VideoUploadSessionQueryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\AdminTestHelper;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class, AdminTestHelper::class)->group('admin', 'query-services');

it('paginates admin categories with filters', function (): void {
    $admin = $this->asAdmin();
    $center = Center::factory()->create();
    Category::factory()->create(['center_id' => $center->id, 'is_active' => true]);
    Category::factory()->create(['center_id' => $center->id, 'is_active' => false]);

    $service = app(AdminCategoryQueryService::class);
    $filters = new CategoryFilters(page: 1, perPage: 15, search: null, isActive: true, parentId: null);
    $page = $service->paginate($admin, $center, $filters);

    expect($page->total())->toBe(1);
});

it('paginates admin pdfs and applies course filter', function (): void {
    $admin = $this->asAdmin();
    $center = Center::factory()->create();
    $courseA = Course::factory()->create(['center_id' => $center->id, 'created_by' => $admin->id]);
    $courseB = Course::factory()->create(['center_id' => $center->id, 'created_by' => $admin->id]);
    $pdfA = Pdf::factory()->create(['center_id' => $center->id, 'created_by' => $admin->id]);
    $pdfB = Pdf::factory()->create(['center_id' => $center->id, 'created_by' => $admin->id]);
    $courseA->pdfs()->attach($pdfA->id, ['order_index' => 1, 'visible' => true]);
    $courseB->pdfs()->attach($pdfB->id, ['order_index' => 1, 'visible' => true]);

    $service = app(AdminPdfQueryService::class);
    $filters = new PdfFilters(page: 1, perPage: 15, courseId: $courseA->id, search: null);
    $page = $service->paginateForCenter($admin, $center, $filters);

    expect($page->total())->toBe(1);
    expect((int) $page->items()[0]->id)->toBe($pdfA->id);
});

it('paginates video upload sessions with status filter', function (): void {
    $admin = $this->asAdmin();
    $center = Center::factory()->create();
    VideoUploadSession::factory()->create([
        'center_id' => $center->id,
        'uploaded_by' => $admin->id,
        'upload_status' => VideoUploadStatus::Ready,
    ]);
    VideoUploadSession::factory()->create([
        'center_id' => $center->id,
        'uploaded_by' => $admin->id,
        'upload_status' => VideoUploadStatus::Failed,
    ]);

    $service = app(VideoUploadSessionQueryService::class);
    $filters = new VideoUploadSessionFilters(page: 1, perPage: 15, status: VideoUploadStatus::Ready->value, centerId: null);
    $page = $service->paginate($admin, $filters);

    expect($page->total())->toBe(1);
});
