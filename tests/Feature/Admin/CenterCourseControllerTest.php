<?php

declare(strict_types=1);

use App\Enums\CourseAccessModel;
use App\Models\Category;
use App\Models\Center;
use App\Models\CenterSetting;
use App\Models\College;
use App\Models\Course;
use App\Models\Grade;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

uses(RefreshDatabase::class)->group('courses', 'admin', 'center');

beforeEach(function (): void {
    $this->withoutMiddleware(EnsureFrontendRequestsAreStateful::class);
    $this->withoutMiddleware(Authenticate::class);
    $this->asAdmin();
});

it('lists center courses', function (): void {
    $center = Center::factory()->create();
    Course::factory()->create([
        'center_id' => $center->id,
        'requires_video_approval' => true,
    ]);
    Course::factory()->create(['center_id' => $center->id]);

    $response = $this->getJson("/api/v1/admin/centers/{$center->id}/courses", $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(2, 'data')
        ->assertJsonStructure([
            'data' => [
                ['requires_video_approval'],
            ],
        ]);
});

it('creates course in center', function (): void {
    $center = Center::factory()->create();
    $payload = [
        'title_translations' => [
            'en' => 'Sample Course',
            'ar' => 'دورة نموذجية',
        ],
        'description_translations' => [
            'en' => 'A course description',
            'ar' => 'وصف الدورة',
        ],
        'category_id' => Category::factory()->create()->id,
        'difficulty' => 'beginner',
        'language' => 'en',
        'requires_video_approval' => true,
        'access_model' => 'video_code',
    ];

    $response = $this->postJson("/api/v1/admin/centers/{$center->id}/courses", $payload, $this->adminHeaders());

    $response->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.title', 'Sample Course')
        ->assertJsonPath('data.title_translations.en', 'Sample Course')
        ->assertJsonPath('data.title_translations.ar', 'دورة نموذجية')
        ->assertJsonPath('data.requires_video_approval', true)
        ->assertJsonPath('data.access_model', 'video_code');
    $this->assertDatabaseHas('courses', [
        'center_id' => $center->id,
        'status' => 0,
        'is_published' => 0,
        'publish_at' => null,
        'requires_video_approval' => 1,
        'access_model' => 'video_code',
        'show_for_all_students' => 1,
    ]);
});

it('creates targeted course with education targets', function (): void {
    $center = Center::factory()->create();
    $category = Category::factory()->create();
    $grade = Grade::factory()->create(['center_id' => $center->id]);
    $school = School::factory()->create(['center_id' => $center->id]);
    $college = College::factory()->create(['center_id' => $center->id]);

    $payload = [
        'title_translations' => [
            'en' => 'Targeted Course',
            'ar' => 'دورة موجهة',
        ],
        'description_translations' => [
            'en' => 'Targeted description',
            'ar' => 'وصف موجه',
        ],
        'category_id' => $category->id,
        'difficulty' => 'beginner',
        'show_for_all_students' => false,
        'grade_ids' => [$grade->id],
        'school_ids' => [$school->id],
        'college_ids' => [$college->id],
    ];

    $response = $this->postJson("/api/v1/admin/centers/{$center->id}/courses", $payload, $this->adminHeaders());

    $response->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.show_for_all_students', false)
        ->assertJsonPath('data.education_targets.grades.0.id', $grade->id)
        ->assertJsonPath('data.education_targets.schools.0.id', $school->id)
        ->assertJsonPath('data.education_targets.colleges.0.id', $college->id);

    $courseId = (int) $response->json('data.id');
    $this->assertDatabaseHas('courses', [
        'id' => $courseId,
        'show_for_all_students' => 0,
    ]);
    $this->assertDatabaseHas('course_grades', ['course_id' => $courseId, 'grade_id' => $grade->id]);
    $this->assertDatabaseHas('course_schools', ['course_id' => $courseId, 'school_id' => $school->id]);
    $this->assertDatabaseHas('course_colleges', ['course_id' => $courseId, 'college_id' => $college->id]);
});

it('rejects invalid title_translations payload when creating course', function (): void {
    $center = Center::factory()->create();

    $response = $this->postJson("/api/v1/admin/centers/{$center->id}/courses", [
        'title_translations' => 'not an array',
        'description_translations' => ['en' => 'A course description'],
        'category_id' => Category::factory()->create()->id,
        'difficulty' => 'beginner',
        'language' => 'en',
    ], $this->adminHeaders());

    $response->assertStatus(422);
});

it('shows course in center', function (): void {
    $center = Center::factory()->create();
    $course = Course::factory()->create(['center_id' => $center->id]);

    $response = $this->getJson("/api/v1/admin/centers/{$center->id}/courses/{$course->id}", $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('data.id', $course->id)
        ->assertJsonPath('data.requires_video_approval', false)
        ->assertJsonPath('data.access_model', 'enrollment');
});

it('falls back requires_video_approval to center settings when course override is null', function (): void {
    $center = Center::factory()->create();
    CenterSetting::factory()->create([
        'center_id' => $center->id,
        'settings' => [
            'requires_video_approval' => true,
        ],
    ]);
    $course = Course::factory()->create([
        'center_id' => $center->id,
        'requires_video_approval' => null,
    ]);

    $response = $this->getJson("/api/v1/admin/centers/{$center->id}/courses/{$course->id}", $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('data.id', $course->id)
        ->assertJsonPath('data.requires_video_approval', true);
});

it('updates course in center', function (): void {
    $center = Center::factory()->create();
    $course = Course::factory()->create([
        'center_id' => $center->id,
        'status' => 0,
        'is_published' => false,
        'access_model' => CourseAccessModel::VideoCode,
    ]);

    $response = $this->putJson("/api/v1/admin/centers/{$center->id}/courses/{$course->id}", [
        'title_translations' => [
            'en' => 'Updated Title',
            'ar' => 'العنوان المحدث',
        ],
        'requires_video_approval' => false,
        'access_model' => 'video_code',
    ], $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('data.title', 'Updated Title')
        ->assertJsonPath('data.title_translations.en', 'Updated Title')
        ->assertJsonPath('data.requires_video_approval', false)
        ->assertJsonPath('data.access_model', 'video_code');

    $this->assertDatabaseHas('courses', [
        'id' => $course->id,
        'requires_video_approval' => 0,
        'access_model' => 'video_code',
    ]);
});

it('blocks switching a course to video_code after creation', function (): void {
    $center = Center::factory()->create();
    $course = Course::factory()->create([
        'center_id' => $center->id,
        'access_model' => CourseAccessModel::Enrollment,
    ]);

    $response = $this->putJson("/api/v1/admin/centers/{$center->id}/courses/{$course->id}", [
        'access_model' => 'video_code',
    ], $this->adminHeaders());

    $response->assertStatus(422)
        ->assertJsonPath('error.code', 'INVALID_STATE')
        ->assertJsonPath(
            'error.message',
            'Course access model cannot be changed after creation. Create a new course instead.'
        );
});

it('blocks switching a course back to enrollment after creation', function (): void {
    $center = Center::factory()->create();
    $course = Course::factory()->create([
        'center_id' => $center->id,
        'access_model' => CourseAccessModel::VideoCode,
    ]);

    $response = $this->putJson("/api/v1/admin/centers/{$center->id}/courses/{$course->id}", [
        'access_model' => 'enrollment',
    ], $this->adminHeaders());

    $response->assertStatus(422)
        ->assertJsonPath('error.code', 'INVALID_STATE')
        ->assertJsonPath(
            'error.message',
            'Course access model cannot be changed after creation. Create a new course instead.'
        );
});

it('switches targeted course to show for all and clears targets', function (): void {
    $center = Center::factory()->create();
    $course = Course::factory()->create([
        'center_id' => $center->id,
        'show_for_all_students' => false,
    ]);
    $grade = Grade::factory()->create(['center_id' => $center->id]);
    $school = School::factory()->create(['center_id' => $center->id]);
    $college = College::factory()->create(['center_id' => $center->id]);
    $course->grades()->sync([$grade->id]);
    $course->schools()->sync([$school->id]);
    $course->colleges()->sync([$college->id]);

    $response = $this->putJson("/api/v1/admin/centers/{$center->id}/courses/{$course->id}", [
        'show_for_all_students' => true,
    ], $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('data.show_for_all_students', true)
        ->assertJsonPath('data.education_targets.grades', [])
        ->assertJsonPath('data.education_targets.schools', [])
        ->assertJsonPath('data.education_targets.colleges', []);

    $this->assertDatabaseHas('courses', [
        'id' => $course->id,
        'show_for_all_students' => 1,
    ]);
    $this->assertDatabaseMissing('course_grades', ['course_id' => $course->id]);
    $this->assertDatabaseMissing('course_schools', ['course_id' => $course->id]);
    $this->assertDatabaseMissing('course_colleges', ['course_id' => $course->id]);
});

it('soft deletes course in center', function (): void {
    $center = Center::factory()->create();
    $course = Course::factory()->create(['center_id' => $center->id]);

    $response = $this->deleteJson("/api/v1/admin/centers/{$center->id}/courses/{$course->id}", [], $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data', null);
    $this->assertSoftDeleted('courses', ['id' => $course->id]);
});

it('returns not found for center mismatch', function (): void {
    $center = Center::factory()->create();
    $otherCenter = Center::factory()->create();
    $course = Course::factory()->create(['center_id' => $otherCenter->id]);

    $response = $this->getJson("/api/v1/admin/centers/{$center->id}/courses/{$course->id}", $this->adminHeaders());

    $response->assertNotFound()
        ->assertJsonPath('error.code', 'NOT_FOUND');
});

it('enforces course manage permission', function (): void {
    $role = Role::factory()->create(['slug' => 'content_admin']);
    $center = Center::factory()->create();
    $admin = User::factory()->create([
        'password' => 'secret123',
        'is_student' => false,
        'center_id' => $center->id,
    ]);
    $admin->roles()->sync([$role->id]);
    $admin->centers()->sync([$center->id => ['type' => 'admin']]);

    $token = (string) Auth::guard('admin')->attempt([
        'email' => $admin->email,
        'password' => 'secret123',
        'is_student' => false,
    ]);

    $response = $this->getJson("/api/v1/admin/centers/{$center->id}/courses", [
        'Authorization' => 'Bearer '.$token,
        'Accept' => 'application/json',
        'X-Api-Key' => config('services.system_api_key'),
    ]);

    $response->assertForbidden()
        ->assertJsonPath('error.code', 'PERMISSION_DENIED');
});
