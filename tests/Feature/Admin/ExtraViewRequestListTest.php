<?php

declare(strict_types=1);

use App\Models\Center;
use App\Models\Course;
use App\Models\ExtraViewRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class)->group('admin', 'extra-view-requests');

it('allows super admin to list requests for specific center', function (): void {
    $super = $this->asAdmin();

    $centerA = Center::factory()->create();
    $centerB = Center::factory()->create();

    ExtraViewRequest::factory()->create(['center_id' => $centerA->id]);
    ExtraViewRequest::factory()->create(['center_id' => $centerB->id]);

    // Super admin accesses center-scoped route
    $responseA = $this->actingAs($super, 'admin')->getJson("/api/v1/admin/centers/{$centerA->id}/extra-view-requests", $this->adminHeaders());
    $responseA->assertOk()->assertJsonCount(1, 'data');

    $responseB = $this->actingAs($super, 'admin')->getJson("/api/v1/admin/centers/{$centerB->id}/extra-view-requests", $this->adminHeaders());
    $responseB->assertOk()->assertJsonCount(1, 'data');
});

it('scopes list to admin center', function (): void {
    $permission = Permission::factory()->create(['name' => 'extra_view.manage']);
    $role = Role::factory()->create(['slug' => 'extra_view_admin']);
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

    ExtraViewRequest::factory()->create(['center_id' => $centerA->id]);
    ExtraViewRequest::factory()->create(['center_id' => $centerB->id]);

    $token = (string) Auth::guard('admin')->attempt([
        'email' => $admin->email,
        'password' => 'secret123',
        'is_student' => false,
    ]);

    // Center admin can access their own center's requests
    $response = $this->getJson("/api/v1/admin/centers/{$centerA->id}/extra-view-requests", [
        'Authorization' => 'Bearer '.$token,
        'Accept' => 'application/json',
        'X-Api-Key' => config('services.system_api_key'),
    ]);

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.center_id', $centerA->id);

    // Center admin cannot access other center's requests
    $blocked = $this->getJson("/api/v1/admin/centers/{$centerB->id}/extra-view-requests", [
        'Authorization' => 'Bearer '.$token,
        'Accept' => 'application/json',
        'X-Api-Key' => config('services.system_api_key'),
    ]);

    $blocked->assertForbidden();
});

it('applies filters and pagination', function (): void {
    $super = $this->asAdmin();
    $center = Center::factory()->create();
    $user = User::factory()->create();

    ExtraViewRequest::factory()->create([
        'center_id' => $center->id,
        'user_id' => $user->id,
        'status' => ExtraViewRequest::STATUS_APPROVED,
        'created_at' => now()->subDays(2),
    ]);
    ExtraViewRequest::factory()->create([
        'center_id' => $center->id,
        'status' => ExtraViewRequest::STATUS_PENDING,
        'created_at' => now()->subDays(10),
    ]);

    $response = $this->actingAs($super, 'admin')->getJson("/api/v1/admin/centers/{$center->id}/extra-view-requests?status=".ExtraViewRequest::STATUS_APPROVED->value.'&user_id='.$user->id.'&date_from='.now()->subDays(3)->toDateString().'&per_page=1', $this->adminHeaders());

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('meta.per_page', 1);
});

it('supports text search filters and requested_at aliases in system scope', function (): void {
    $this->asAdmin();
    $center = Center::factory()->create();

    $matchingStudent = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
        'name' => 'Search Student',
        'phone' => '15551234567',
    ]);
    $otherStudent = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
        'name' => 'Other Student',
        'phone' => '16660000000',
    ]);

    $matchingCourse = Course::factory()->create([
        'center_id' => $center->id,
        'title_translations' => ['en' => 'Physics Search Course', 'ar' => 'Physics Search Course'],
    ]);
    $otherCourse = Course::factory()->create([
        'center_id' => $center->id,
        'title_translations' => ['en' => 'Chemistry Course', 'ar' => 'Chemistry Course'],
    ]);

    $matchingVideo = Video::factory()->create([
        'title_translations' => ['en' => 'Search Video Intro', 'ar' => 'Search Video Intro'],
    ]);
    $otherVideo = Video::factory()->create([
        'title_translations' => ['en' => 'General Video', 'ar' => 'General Video'],
    ]);

    ExtraViewRequest::factory()->create([
        'user_id' => $matchingStudent->id,
        'course_id' => $matchingCourse->id,
        'video_id' => $matchingVideo->id,
        'center_id' => $center->id,
        'reason' => 'Need extra view due to weak internet',
        'created_at' => now()->subDays(2),
    ]);

    ExtraViewRequest::factory()->create([
        'user_id' => $otherStudent->id,
        'course_id' => $otherCourse->id,
        'video_id' => $otherVideo->id,
        'center_id' => $center->id,
        'created_at' => now()->subDays(20),
    ]);

    $from = now()->subDays(5)->toDateString();
    $to = now()->toDateString();

    $response = $this->getJson(
        "/api/v1/admin/extra-view-requests?search=Search&course_title=Physics&video_title=Intro&requested_at_from={$from}&requested_at_to={$to}",
        $this->adminHeaders()
    );

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.user.id', $matchingStudent->id)
        ->assertJsonPath('data.0.user.email', $matchingStudent->email)
        ->assertJsonPath('data.0.user.phone', $matchingStudent->phone)
        ->assertJsonPath('data.0.course.id', $matchingCourse->id)
        ->assertJsonPath('data.0.video.id', $matchingVideo->id)
        ->assertJsonPath('data.0.reason', 'Need extra view due to weak internet')
        ->assertJsonPath('data.0.center_id', $center->id);

    $response->assertJsonStructure([
        'data' => [[
            'requested_at',
            'created_at',
            'updated_at',
            'decider',
            'decision_reason',
            'granted_views',
        ]],
    ]);
});

it('lists requests in system scope with center and course filters', function (): void {
    $super = $this->asAdmin();
    $centerA = Center::factory()->create();
    $centerB = Center::factory()->create();
    $decider = User::factory()->create(['is_student' => false, 'center_id' => null]);

    $courseA = Course::factory()->create(['center_id' => $centerA->id]);
    $courseB = Course::factory()->create(['center_id' => $centerB->id]);
    $videoA = Video::factory()->create();
    $videoB = Video::factory()->create();

    ExtraViewRequest::factory()->create([
        'center_id' => $centerA->id,
        'course_id' => $courseA->id,
        'video_id' => $videoA->id,
        'status' => ExtraViewRequest::STATUS_APPROVED,
        'decided_by' => $decider->id,
    ]);
    ExtraViewRequest::factory()->create([
        'center_id' => $centerB->id,
        'course_id' => $courseB->id,
        'video_id' => $videoB->id,
        'status' => ExtraViewRequest::STATUS_APPROVED,
        'decided_by' => $decider->id,
    ]);

    $response = $this->actingAs($super, 'admin')->getJson(
        "/api/v1/admin/extra-view-requests?center_id={$centerA->id}&course_id={$courseA->id}&decided_by={$decider->id}",
        $this->adminHeaders()
    );

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.center_id', $centerA->id);
});

it('rejects access without permission', function (): void {
    $center = Center::factory()->create();
    $admin = User::factory()->create([
        'password' => 'secret123',
        'is_student' => false,
        'center_id' => $center->id,
    ]);

    $token = (string) Auth::guard('admin')->attempt([
        'email' => $admin->email,
        'password' => 'secret123',
        'is_student' => false,
    ]);

    $response = $this->getJson("/api/v1/admin/centers/{$center->id}/extra-view-requests", [
        'Authorization' => 'Bearer '.$token,
        'Accept' => 'application/json',
        'X-Api-Key' => config('services.system_api_key'),
    ]);

    $response->assertForbidden();
});

it('allows centerless non-super admin with permission to list system scope extra view requests', function (): void {
    $permission = Permission::firstOrCreate(['name' => 'extra_view.manage'], [
        'description' => 'Permission: extra_view.manage',
    ]);
    $role = Role::firstOrCreate(['slug' => 'extra_view_manager'], [
        'name' => 'extra view manager',
        'name_translations' => ['en' => 'extra view manager', 'ar' => 'extra view manager'],
        'description_translations' => ['en' => 'Extra view management role', 'ar' => 'Extra view management role'],
    ]);
    $role->permissions()->syncWithoutDetaching([$permission->id]);

    $admin = User::factory()->create([
        'password' => 'secret123',
        'is_student' => false,
        'center_id' => null,
    ]);
    $admin->roles()->syncWithoutDetaching([$role->id]);

    $token = (string) Auth::guard('admin')->attempt([
        'email' => $admin->email,
        'password' => 'secret123',
        'is_student' => false,
    ]);

    $centerA = Center::factory()->create();
    $centerB = Center::factory()->create();
    ExtraViewRequest::factory()->create(['center_id' => $centerA->id]);
    ExtraViewRequest::factory()->create(['center_id' => $centerB->id]);

    $response = $this->getJson('/api/v1/admin/extra-view-requests', [
        'Authorization' => 'Bearer '.$token,
        'Accept' => 'application/json',
        'X-Api-Key' => config('services.system_api_key'),
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(2, 'data');
});
