<?php

declare(strict_types=1);

use App\Enums\VideoUploadStatus;
use App\Models\Center;
use App\Models\Course;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoUploadSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class)->group('videos');

it('lists videos with upload sessions for admin center', function (): void {
    $center = Center::factory()->create();
    $otherCenter = Center::factory()->create();

    $admin = $this->asAdmin();
    $admin->update(['center_id' => $center->id]);
    $admin->update(['center_id' => $center->id]);

    /** @var VideoUploadSession $session */
    $session = VideoUploadSession::factory()->create([
        'center_id' => $center->id,
        'upload_status' => VideoUploadStatus::Failed,
        'error_message' => 'Encoding failed',
    ]);

    /** @var Video $video */
    $video = Video::factory()->create([
        'center_id' => $center->id,
        'created_by' => $admin->id,
        'upload_session_id' => $session->id,
        'encoding_status' => VideoUploadStatus::Processing,
        'lifecycle_status' => 1,
    ]);

    $otherAdmin = User::factory()->create([
        'is_student' => false,
        'center_id' => $otherCenter->id,
    ]);

    VideoUploadSession::factory()->create(['center_id' => $otherCenter->id]);
    Video::factory()->create([
        'center_id' => $otherCenter->id,
        'created_by' => $otherAdmin->id,
    ]);

    $response = $this->getJson("/api/v1/admin/centers/{$center->id}/videos?per_page=10", $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.id', $video->id)
        ->assertJsonPath('data.0.encoding_status', VideoUploadStatus::Processing->value)
        ->assertJsonPath('data.0.lifecycle_status', 1)
        ->assertJsonPath('data.0.upload_sessions.0.id', $session->id)
        ->assertJsonPath('data.0.upload_sessions.0.upload_status', VideoUploadStatus::Failed->value)
        ->assertJsonPath('data.0.upload_sessions.0.error_message', 'Encoding failed');

    $json = $response->json();
    expect($json['data'][0])->not->toHaveKey('playback_url')
        ->and($json['data'][0])->not->toHaveKey('source_url');
});

it('filters videos by title search', function (): void {
    $center = Center::factory()->create();
    $admin = $this->asAdmin();
    $admin->update(['center_id' => $center->id]);
    Video::factory()->create([
        'center_id' => $center->id,
        'title_translations' => ['en' => 'Alpha Intro'],
    ]);
    Video::factory()->create([
        'center_id' => $center->id,
        'title_translations' => ['en' => 'Beta Intro'],
    ]);

    $response = $this->getJson("/api/v1/admin/centers/{$center->id}/videos?search=Alpha", $this->adminHeaders());

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Alpha Intro');
});

it('filters videos by course', function (): void {
    $center = Center::factory()->create();
    $admin = $this->asAdmin();
    $admin->update(['center_id' => $center->id]);
    $courseA = Course::factory()->create(['center_id' => $center->id]);
    $courseB = Course::factory()->create(['center_id' => $center->id]);

    $videoA = Video::factory()->create([
        'center_id' => $center->id,
        'title_translations' => ['en' => 'Course A Video'],
    ]);
    $videoB = Video::factory()->create([
        'center_id' => $center->id,
        'title_translations' => ['en' => 'Course B Video'],
    ]);

    $courseA->videos()->attach($videoA->id, ['section_id' => null]);
    $courseB->videos()->attach($videoB->id, ['section_id' => null]);

    $response = $this->getJson("/api/v1/admin/centers/{$center->id}/videos?course_id=".$courseA->id, $this->adminHeaders());

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Course A Video');
});

it('scopes videos to admin center for non super admins', function (): void {
    $permission = Permission::firstOrCreate(['name' => 'video.manage'], [
        'description' => 'Permission: video.manage',
    ]);
    $role = Role::factory()->create(['slug' => 'video_admin']);
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

    Video::factory()->create([
        'center_id' => $centerA->id,
        'title_translations' => ['en' => 'Center A Video'],
        'created_by' => User::factory()->create([
            'center_id' => $centerA->id,
            'phone' => '1000000001',
        ])->id,
    ]);
    Video::factory()->create([
        'center_id' => $centerB->id,
        'title_translations' => ['en' => 'Center B Video'],
        'created_by' => User::factory()->create([
            'center_id' => $centerB->id,
            'phone' => '1000000002',
        ])->id,
    ]);

    $token = (string) Auth::guard('admin')->attempt([
        'email' => $admin->email,
        'password' => 'secret123',
        'is_student' => false,
    ]);

    $response = $this->getJson("/api/v1/admin/centers/{$centerA->id}/videos", [
        'Authorization' => 'Bearer '.$token,
        'Accept' => 'application/json',
        'X-Api-Key' => config('services.system_api_key'),
    ]);

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Center A Video');
});

it('requires admin authentication', function (): void {
    $center = Center::factory()->create();
    $response = $this->getJson("/api/v1/admin/centers/{$center->id}/videos", [
        'X-Api-Key' => config('services.system_api_key'),
    ]);

    $response->assertStatus(401);
});
