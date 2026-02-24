<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Center;
use App\Models\Course;
use App\Models\Role;
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
    Course::factory()->create(['center_id' => $center->id]);
    Course::factory()->create(['center_id' => $center->id]);

    $response = $this->getJson("/api/v1/admin/centers/{$center->id}/courses", $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(2, 'data');
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
    ];

    $response = $this->postJson("/api/v1/admin/centers/{$center->id}/courses", $payload, $this->adminHeaders());

    $response->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.title', 'Sample Course')
        ->assertJsonPath('data.title_translations.en', 'Sample Course')
        ->assertJsonPath('data.title_translations.ar', 'دورة نموذجية');
    $this->assertDatabaseHas('courses', [
        'center_id' => $center->id,
        'status' => 0,
        'is_published' => 0,
        'publish_at' => null,
    ]);
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

    $response->assertOk()->assertJsonPath('data.id', $course->id);
});

it('updates course in center', function (): void {
    $center = Center::factory()->create();
    $course = Course::factory()->create(['center_id' => $center->id, 'status' => 0, 'is_published' => false]);

    $response = $this->putJson("/api/v1/admin/centers/{$center->id}/courses/{$course->id}", [
        'title_translations' => [
            'en' => 'Updated Title',
            'ar' => 'العنوان المحدث',
        ],
    ], $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('data.title', 'Updated Title')
        ->assertJsonPath('data.title_translations.en', 'Updated Title');
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
