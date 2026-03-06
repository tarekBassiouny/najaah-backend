<?php

declare(strict_types=1);

use App\Models\Center;
use App\Models\Course;
use App\Models\Instructor;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\Helpers\AdminTestHelper;

uses(RefreshDatabase::class, AdminTestHelper::class)->group('instructors', 'admin');

it('filters instructors by name search', function (): void {
    $this->asAdmin();
    $center = Center::factory()->create();

    Instructor::factory()->create([
        'center_id' => $center->id,
        'name_translations' => ['en' => 'Alpha Instructor'],
    ]);
    Instructor::factory()->create([
        'center_id' => $center->id,
        'name_translations' => ['en' => 'Beta Instructor'],
    ]);

    $response = $this->getJson("/api/v1/admin/centers/{$center->id}/instructors?search=Alpha", $this->adminHeaders());

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Alpha Instructor');
});

it('filters instructors by course', function (): void {
    $this->asAdmin();
    $center = Center::factory()->create();

    $courseA = Course::factory()->create(['center_id' => $center->id]);
    $courseB = Course::factory()->create(['center_id' => $center->id]);

    $instructorA = Instructor::factory()->create([
        'center_id' => $center->id,
        'name_translations' => ['en' => 'Course A Instructor'],
    ]);
    $instructorB = Instructor::factory()->create([
        'center_id' => $center->id,
        'name_translations' => ['en' => 'Course B Instructor'],
    ]);

    $courseA->instructors()->attach($instructorA->id, ['role' => 'primary']);
    $courseB->instructors()->attach($instructorB->id, ['role' => 'primary']);

    $response = $this->getJson("/api/v1/admin/centers/{$center->id}/instructors?course_id={$courseA->id}", $this->adminHeaders());

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Course A Instructor')
        ->assertJsonPath('data.0.courses.0.id', $courseA->id);
});

it('returns assigned courses in instructor list', function (): void {
    $this->asAdmin();
    $center = Center::factory()->create();

    $courseA = Course::factory()->create([
        'center_id' => $center->id,
        'title_translations' => ['en' => 'Biology'],
    ]);
    $courseB = Course::factory()->create([
        'center_id' => $center->id,
        'title_translations' => ['en' => 'Chemistry'],
    ]);

    $instructor = Instructor::factory()->create([
        'center_id' => $center->id,
        'name_translations' => ['en' => 'Assigned Instructor'],
    ]);

    $courseA->instructors()->attach($instructor->id, ['role' => 'primary']);
    $courseB->instructors()->attach($instructor->id, ['role' => 'assistant']);

    $response = $this->getJson("/api/v1/admin/centers/{$center->id}/instructors", $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('data.0.name', 'Assigned Instructor')
        ->assertJsonPath('data.0.courses.0.id', fn ($id) => in_array((int) $id, [$courseA->id, $courseB->id], true))
        ->assertJsonPath('data.0.courses.1.id', fn ($id) => in_array((int) $id, [$courseA->id, $courseB->id], true))
        ->assertJsonPath('data.0.courses.0.title', fn ($title) => is_string($title) && $title !== '')
        ->assertJsonPath('data.0.courses.1.title', fn ($title) => is_string($title) && $title !== '');
});

it('allows super admin to filter instructors by center', function (): void {
    $this->asAdmin();

    $centerA = Center::factory()->create();
    $centerB = Center::factory()->create();

    Instructor::factory()->create([
        'center_id' => $centerA->id,
        'name_translations' => ['en' => 'Center A Instructor'],
    ]);
    Instructor::factory()->create([
        'center_id' => $centerB->id,
        'name_translations' => ['en' => 'Center B Instructor'],
    ]);

    // Super admin accesses instructors via center route
    $response = $this->getJson("/api/v1/admin/centers/{$centerA->id}/instructors", $this->adminHeaders());

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Center A Instructor');
});

it('scopes instructors to admin center', function (): void {
    $permission = Permission::firstOrCreate(['name' => 'instructor.manage'], [
        'description' => 'Permission: instructor.manage',
    ]);
    $role = Role::factory()->create(['slug' => 'instructor_admin']);
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

    Instructor::factory()->create([
        'center_id' => $centerA->id,
        'name_translations' => ['en' => 'Center A Instructor'],
    ]);
    Instructor::factory()->create([
        'center_id' => $centerB->id,
        'name_translations' => ['en' => 'Center B Instructor'],
    ]);

    $token = (string) Auth::guard('admin')->attempt([
        'email' => $admin->email,
        'password' => 'secret123',
        'is_student' => false,
    ]);

    // Center admin accesses their own center's instructors
    $response = $this->getJson("/api/v1/admin/centers/{$centerA->id}/instructors", [
        'Authorization' => 'Bearer '.$token,
        'Accept' => 'application/json',
        'X-Api-Key' => $centerA->api_key,
    ]);

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Center A Instructor');
});
