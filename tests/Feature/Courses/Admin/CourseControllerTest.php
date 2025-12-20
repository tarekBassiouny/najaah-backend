<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Center;
use App\Models\Course;
use App\Models\Instructor;
use App\Models\Pdf;
use App\Models\Permission;
use App\Models\Pivots\CoursePdf;
use App\Models\Pivots\CourseVideo;
use App\Models\Role;
use App\Models\Section;
use App\Models\User;
use App\Models\Video;
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

    $response = $this->getJson('/api/v1/admin/courses');

    $response->assertOk()->assertJsonPath('success', true);
});

it('filters courses by title search', function (): void {
    Course::factory()->create([
        'title_translations' => ['en' => 'Alpha Biology'],
    ]);
    Course::factory()->create([
        'title_translations' => ['en' => 'Beta Physics'],
    ]);

    $response = $this->getJson('/api/v1/admin/courses?search=Alpha');

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

    $response = $this->getJson('/api/v1/admin/courses?category_id='.$category->id.'&primary_instructor_id='.$instructor->id);

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

    $response = $this->getJson('/api/v1/admin/courses?center_id='.$centerA->id);

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
    ]);

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Center A Course');
});

it('creates course', function (): void {
    $payload = [
        'title' => 'Sample Course',
        'description' => 'A course description',
        'category_id' => Category::factory()->create()->id,
        'center_id' => Center::factory()->create()->id,
        'difficulty' => 'beginner',
        'language' => 'en',
        'price' => 0,
    ];

    $response = $this->postJson('/api/v1/admin/courses', $payload);

    $response->assertCreated()->assertJsonPath('success', true);
    $response->assertJsonPath('data.title', 'Sample Course');
    $this->assertDatabaseHas('courses', ['title_translations->en' => 'Sample Course']);
});

it('shows course', function (): void {
    $course = Course::factory()->create();

    $response = $this->getJson("/api/v1/admin/courses/{$course->id}");

    $response->assertOk()->assertJsonPath('data.id', $course->id);
});

it('updates course', function (): void {
    $course = Course::factory()->create();

    $response = $this->putJson("/api/v1/admin/courses/{$course->id}", [
        'title' => 'Updated Title',
    ]);

    $response->assertOk()->assertJsonPath('data.title', 'Updated Title');
    $this->assertDatabaseHas('courses', ['id' => $course->id, 'title_translations->en' => 'Updated Title']);
});

it('soft deletes course', function (): void {
    $course = Course::factory()->create();

    $response = $this->deleteJson("/api/v1/admin/courses/{$course->id}");

    $response->assertNoContent();
    $this->assertSoftDeleted('courses', ['id' => $course->id]);
});

it('adds section', function (): void {
    $course = Course::factory()->create();

    $response = $this->postJson("/api/v1/admin/courses/{$course->id}/sections", [
        'title' => 'Section 1',
        'description' => 'Description',
    ]);

    $response->assertOk()->assertJsonPath('success', true);
    $this->assertDatabaseHas('sections', ['course_id' => $course->id, 'title_translations->en' => 'Section 1']);
});

it('reorders sections', function (): void {
    $course = Course::factory()->create();
    $sections = Section::factory()->count(2)->create(['course_id' => $course->id]);
    $ordered = $sections->pluck('id')->reverse()->values()->all();

    $response = $this->putJson("/api/v1/admin/courses/{$course->id}/sections/reorder", [
        'sections' => $ordered,
    ]);

    $response->assertOk()->assertJsonPath('success', true);
});

it('toggles section visibility', function (): void {
    $course = Course::factory()->create();
    $section = Section::factory()->create(['course_id' => $course->id, 'visible' => true]);

    $response = $this->patchJson("/api/v1/admin/courses/{$course->id}/sections/{$section->id}/visibility");

    $response->assertOk()->assertJsonPath('success', true);
    $section->refresh();
    expect($section->visible)->toBeFalse();
});

it('assigns video', function (): void {
    $course = Course::factory()->create();
    $video = Video::factory()->create([
        'created_by' => $course->created_by,
        'encoding_status' => 3,
        'lifecycle_status' => 2,
    ]);

    $response = $this->postJson("/api/v1/admin/courses/{$course->id}/videos", [
        'video_id' => $video->id,
    ]);

    $response->assertCreated()->assertJsonPath('success', true);
    $this->assertDatabaseHas('course_video', ['course_id' => $course->id, 'video_id' => $video->id]);
});

it('removes video', function (): void {
    $course = Course::factory()->create();
    $video = Video::factory()->create(['created_by' => $course->created_by]);
    CourseVideo::create([
        'course_id' => $course->id,
        'video_id' => $video->id,
        'order_index' => 1,
        'visible' => true,
    ]);

    $response = $this->deleteJson("/api/v1/admin/courses/{$course->id}/videos/{$video->id}");

    $response->assertOk()->assertJsonPath('success', true);
    $this->assertSoftDeleted('course_video', ['course_id' => $course->id, 'video_id' => $video->id]);
});

it('assigns pdf', function (): void {
    $course = Course::factory()->create();
    $pdf = Pdf::factory()->create(['created_by' => $course->created_by]);

    $response = $this->postJson("/api/v1/admin/courses/{$course->id}/pdfs", [
        'pdf_id' => $pdf->id,
    ]);

    $response->assertCreated()->assertJsonPath('success', true);
    $this->assertDatabaseHas('course_pdf', ['course_id' => $course->id, 'pdf_id' => $pdf->id]);
});

it('removes pdf', function (): void {
    $course = Course::factory()->create();
    $pdf = Pdf::factory()->create(['created_by' => $course->created_by]);
    CoursePdf::create([
        'course_id' => $course->id,
        'pdf_id' => $pdf->id,
        'order_index' => 1,
        'visible' => true,
    ]);

    $response = $this->deleteJson("/api/v1/admin/courses/{$course->id}/pdfs/{$pdf->id}");

    $response->assertOk()->assertJsonPath('success', true);
    $this->assertSoftDeleted('course_pdf', ['course_id' => $course->id, 'pdf_id' => $pdf->id]);
});

it('publishes course', function (): void {
    $course = Course::factory()->create(['status' => 0, 'is_published' => false]);
    Section::factory()->create(['course_id' => $course->id]);
    $video = Video::factory()->create([
        'lifecycle_status' => 2,
        'encoding_status' => 3,
        'upload_session_id' => null,
    ]);
    CourseVideo::create([
        'course_id' => $course->id,
        'video_id' => $video->id,
        'order_index' => 1,
        'visible' => true,
    ]);

    $response = $this->postJson("/api/v1/admin/courses/{$course->id}/publish");

    $response->assertOk()->assertJsonPath('success', true);
});

it('clones course', function (): void {
    $course = Course::factory()->create();

    $response = $this->postJson("/api/v1/admin/courses/{$course->id}/clone", [
        'options' => [],
    ]);

    $response->assertCreated()->assertJsonPath('success', true);
});
