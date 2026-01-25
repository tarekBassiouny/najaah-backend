<?php

declare(strict_types=1);

use App\Enums\PdfUploadStatus;
use App\Enums\VideoUploadStatus;
use App\Models\Category;
use App\Models\Center;
use App\Models\Course;
use App\Models\Instructor;
use App\Models\Pdf;
use App\Models\PdfUploadSession;
use App\Models\Permission;
use App\Models\Pivots\CoursePdf;
use App\Models\Pivots\CourseVideo;
use App\Models\Role;
use App\Models\Section;
use App\Models\Translation;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoUploadSession;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

uses(RefreshDatabase::class)->group('courses', 'admin');

beforeEach(function (): void {
    $this->withoutMiddleware(EnsureFrontendRequestsAreStateful::class);
    $this->withoutMiddleware(Authenticate::class);
    $this->asAdmin();
});

it('lists courses', function (): void {
    Course::factory()->count(2)->create();

    $response = $this->getJson('/api/v1/admin/courses', $this->adminHeaders());

    $response->assertOk()->assertJsonPath('success', true);
});

it('filters courses by title search', function (): void {
    Course::factory()->create([
        'title_translations' => ['en' => 'Alpha Biology'],
    ]);
    Course::factory()->create([
        'title_translations' => ['en' => 'Beta Physics'],
    ]);

    $response = $this->getJson('/api/v1/admin/courses?search=Alpha', $this->adminHeaders());

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Alpha Biology');
});

it('keeps search stable when translations exist', function (): void {
    $course = Course::factory()->create([
        'title_translations' => ['en' => 'Alpha Biology'],
    ]);

    Translation::create([
        'translatable_type' => Course::class,
        'translatable_id' => $course->id,
        'field' => 'title',
        'locale' => 'ar',
        'value' => 'Arabic Biology',
    ]);

    $response = $this->getJson('/api/v1/admin/courses?search=Alpha', $this->adminHeaders());

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Alpha Biology');
});

it('filters courses by category and primary instructor', function (): void {
    $category = Category::factory()->create();
    $instructor = Instructor::factory()->create();

    Course::factory()->create([
        'category_id' => $category->id,
        'primary_instructor_id' => $instructor->id,
        'title_translations' => ['en' => 'Filtered Course'],
    ]);
    Course::factory()->create([
        'title_translations' => ['en' => 'Other Course'],
    ]);

    $response = $this->getJson('/api/v1/admin/courses?category_id='.$category->id.'&primary_instructor_id='.$instructor->id, $this->adminHeaders());

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Filtered Course');
});

it('allows super admin to filter courses by center', function (): void {
    $centerA = Center::factory()->create();
    $centerB = Center::factory()->create();

    Course::factory()->create([
        'center_id' => $centerA->id,
        'title_translations' => ['en' => 'Center A Course'],
    ]);
    Course::factory()->create([
        'center_id' => $centerB->id,
        'title_translations' => ['en' => 'Center B Course'],
    ]);

    $response = $this->getJson('/api/v1/admin/courses?center_id='.$centerA->id, $this->adminHeaders());

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Center A Course');
});

it('scopes courses to admin center when not super admin', function (): void {
    $permission = Permission::firstOrCreate(['name' => 'course.manage'], [
        'description' => 'Permission: course.manage',
    ]);
    $role = Role::factory()->create(['slug' => 'course_admin']);
    $role->permissions()->sync([$permission->id]);

    $centerA = Center::factory()->create();
    $centerB = Center::factory()->create();

    $admin = User::factory()->create([
        'password' => 'secret123',
        'is_student' => false,
        'center_id' => $centerA->id,
    ]);
    $admin->roles()->sync([$role->id]);
    $admin->centers()->sync([$centerA->id => ['type' => 'admin']]);

    Course::factory()->create([
        'center_id' => $centerA->id,
        'title_translations' => ['en' => 'Center A Course'],
    ]);
    Course::factory()->create([
        'center_id' => $centerB->id,
        'title_translations' => ['en' => 'Center B Course'],
    ]);

    $token = (string) Auth::guard('admin')->attempt([
        'email' => $admin->email,
        'password' => 'secret123',
        'is_student' => false,
    ]);

    $response = $this->getJson('/api/v1/admin/courses?center_id='.$centerB->id, [
        'Authorization' => 'Bearer '.$token,
        'Accept' => 'application/json',
        'X-Api-Key' => config('services.system_api_key'),
    ]);

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Center A Course');
});

it('adds section', function (): void {
    $center = Center::factory()->create();
    $course = Course::factory()->create(['center_id' => $center->id]);

    $response = $this->postJson("/api/v1/admin/centers/{$center->id}/courses/{$course->id}/sections", [
        'title_translations' => ['en' => 'Section 1', 'ar' => 'القسم 1'],
        'description_translations' => ['en' => 'Description', 'ar' => 'الوصف'],
    ], $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.title', 'Section 1');
    $section = Section::where('course_id', $course->id)->latest('id')->first();
    expect($section)->not->toBeNull();
});

it('reorders sections', function (): void {
    $center = Center::factory()->create();
    $course = Course::factory()->create(['center_id' => $center->id]);
    $sections = Section::factory()->count(2)->create(['course_id' => $course->id]);
    $ordered = $sections->pluck('id')->reverse()->values()->all();

    $response = $this->putJson("/api/v1/admin/centers/{$center->id}/courses/{$course->id}/sections/reorder", [
        'sections' => $ordered,
    ], $this->adminHeaders());

    $response->assertOk()->assertJsonPath('success', true);
});

it('toggles section visibility', function (): void {
    $center = Center::factory()->create();
    $course = Course::factory()->create(['center_id' => $center->id]);
    $section = Section::factory()->create(['course_id' => $course->id, 'visible' => true]);

    $response = $this->patchJson("/api/v1/admin/centers/{$center->id}/courses/{$course->id}/sections/{$section->id}/visibility", [], $this->adminHeaders());

    $response->assertOk()->assertJsonPath('success', true);
    $section->refresh();
    expect($section->visible)->toBeFalse();
});

it('assigns video', function (): void {
    $center = Center::factory()->create();
    $course = Course::factory()->create(['center_id' => $center->id]);
    $session = VideoUploadSession::factory()->create([
        'center_id' => $center->id,
        'uploaded_by' => $course->created_by,
        'upload_status' => VideoUploadStatus::Ready,
        'expires_at' => now()->addDay(),
    ]);
    $video = Video::factory()->create([
        'center_id' => $center->id,
        'created_by' => $course->created_by,
        'encoding_status' => 3,
        'lifecycle_status' => 2,
        'upload_session_id' => $session->id,
    ]);

    $response = $this->postJson("/api/v1/admin/centers/{$center->id}/courses/{$course->id}/videos", [
        'video_id' => $video->id,
    ], $this->adminHeaders());

    $response->assertCreated()->assertJsonPath('success', true);
    $this->assertDatabaseHas('course_video', ['course_id' => $course->id, 'video_id' => $video->id]);
});

it('removes video', function (): void {
    $center = Center::factory()->create();
    $course = Course::factory()->create(['center_id' => $center->id]);
    $video = Video::factory()->create([
        'center_id' => $center->id,
        'created_by' => $course->created_by,
    ]);
    CourseVideo::create([
        'course_id' => $course->id,
        'video_id' => $video->id,
        'order_index' => 1,
        'visible' => true,
    ]);

    $response = $this->deleteJson("/api/v1/admin/centers/{$center->id}/courses/{$course->id}/videos/{$video->id}", [], $this->adminHeaders());

    $response->assertOk()->assertJsonPath('success', true);
    $this->assertSoftDeleted('course_video', ['course_id' => $course->id, 'video_id' => $video->id]);
});

it('assigns pdf', function (): void {
    $center = Center::factory()->create();
    $course = Course::factory()->create(['center_id' => $center->id]);
    $session = PdfUploadSession::factory()->create([
        'center_id' => $center->id,
        'created_by' => $course->created_by,
        'upload_status' => PdfUploadStatus::Ready,
    ]);
    $pdf = Pdf::factory()->create([
        'center_id' => $center->id,
        'created_by' => $course->created_by,
        'upload_session_id' => $session->id,
    ]);

    $response = $this->postJson("/api/v1/admin/centers/{$center->id}/courses/{$course->id}/pdfs", [
        'pdf_id' => $pdf->id,
    ], $this->adminHeaders());

    $response->assertCreated()->assertJsonPath('success', true);
    $this->assertDatabaseHas('course_pdf', ['course_id' => $course->id, 'pdf_id' => $pdf->id]);
});

it('removes pdf', function (): void {
    $center = Center::factory()->create();
    $course = Course::factory()->create(['center_id' => $center->id]);
    $pdf = Pdf::factory()->create([
        'center_id' => $center->id,
        'created_by' => $course->created_by,
    ]);
    CoursePdf::create([
        'course_id' => $course->id,
        'pdf_id' => $pdf->id,
        'order_index' => 1,
        'visible' => true,
    ]);

    $response = $this->deleteJson("/api/v1/admin/centers/{$center->id}/courses/{$course->id}/pdfs/{$pdf->id}", [], $this->adminHeaders());

    $response->assertOk()->assertJsonPath('success', true);
    $this->assertSoftDeleted('course_pdf', ['course_id' => $course->id, 'pdf_id' => $pdf->id]);
});

it('publishes course', function (): void {
    $course = Course::factory()->create(['status' => 0, 'is_published' => false]);
    Section::factory()->create(['course_id' => $course->id]);
    $session = VideoUploadSession::factory()->create([
        'center_id' => $course->center_id,
        'uploaded_by' => $course->created_by,
        'upload_status' => VideoUploadStatus::Ready,
        'expires_at' => now()->addDay(),
    ]);
    $video = Video::factory()->create([
        'center_id' => $course->center_id,
        'lifecycle_status' => 2,
        'encoding_status' => 3,
        'upload_session_id' => $session->id,
    ]);
    CourseVideo::create([
        'course_id' => $course->id,
        'video_id' => $video->id,
        'order_index' => 1,
        'visible' => true,
    ]);

    $response = $this->postJson("/api/v1/admin/courses/{$course->id}/publish", [], $this->adminHeaders());

    $response->assertOk()->assertJsonPath('success', true);
});

it('clones course', function (): void {
    $course = Course::factory()->create();

    $response = $this->postJson("/api/v1/admin/courses/{$course->id}/clone", [
        'options' => [],
    ], $this->adminHeaders());

    $response->assertCreated()->assertJsonPath('success', true);
});
