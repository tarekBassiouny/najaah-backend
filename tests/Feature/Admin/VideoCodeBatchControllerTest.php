<?php

declare(strict_types=1);

use App\Enums\CourseAccessModel;
use App\Models\Center;
use App\Models\CenterSetting;
use App\Models\Course;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoAccess;
use App\Models\VideoCodeBatch;
use App\Models\VideoCodeRedemption;
use App\Services\Evolution\EvolutionApiClient;
use App\Services\VideoAccess\VideoCodeGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\Helpers\AdminTestHelper;

uses(RefreshDatabase::class, AdminTestHelper::class)->group('video-code-batches', 'admin');

function attachVideoToCourseForVideoCodeBatches(Course $course, Video $video): void
{
    $course->videos()->attach($video->id, [
        'section_id' => null,
        'order_index' => 1,
        'visible' => true,
        'view_limit_override' => null,
    ]);
}

it('creates a video code batch for an explicit course video route', function (): void {
    $center = Center::factory()->create();
    $course = Course::factory()->create([
        'center_id' => $center->id,
        'access_model' => CourseAccessModel::VideoCode,
    ]);
    $video = Video::factory()->create([
        'center_id' => $center->id,
    ]);
    attachVideoToCourseForVideoCodeBatches($course, $video);

    $this->asCenterAdmin($center);

    $response = $this->postJson(
        "/api/v1/admin/centers/{$center->id}/courses/{$course->id}/videos/{$video->id}/code-batches",
        [
            'quantity' => 25,
            'view_limit_per_code' => 3,
        ],
        $this->adminHeaders()
    );

    $response->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.course_id', $course->id)
        ->assertJsonPath('data.video_id', $video->id);

    $this->assertDatabaseHas('video_code_batches', [
        'course_id' => $course->id,
        'video_id' => $video->id,
        'center_id' => $center->id,
        'quantity' => 25,
        'view_limit_per_code' => 3,
    ]);
});

it('defaults the batch view limit from center policy settings when omitted', function (): void {
    $center = Center::factory()->create();
    CenterSetting::factory()->create([
        'center_id' => $center->id,
        'settings' => [
            'video_code_batch_default_view_limit' => 7,
        ],
    ]);

    $course = Course::factory()->create([
        'center_id' => $center->id,
        'access_model' => CourseAccessModel::VideoCode,
    ]);
    $video = Video::factory()->create([
        'center_id' => $center->id,
    ]);
    attachVideoToCourseForVideoCodeBatches($course, $video);

    $this->asCenterAdmin($center);

    $response = $this->postJson(
        "/api/v1/admin/centers/{$center->id}/courses/{$course->id}/videos/{$video->id}/code-batches",
        [
            'quantity' => 25,
        ],
        $this->adminHeaders()
    );

    $response->assertCreated()
        ->assertJsonPath('data.view_limit_per_code', 7);

    $this->assertDatabaseHas('video_code_batches', [
        'course_id' => $course->id,
        'video_id' => $video->id,
        'center_id' => $center->id,
        'quantity' => 25,
        'view_limit_per_code' => 7,
    ]);
});

it('rejects creating a batch above the center max quantity setting', function (): void {
    $center = Center::factory()->create();
    CenterSetting::factory()->create([
        'center_id' => $center->id,
        'settings' => [
            'video_code_batch_max_quantity' => 50,
        ],
    ]);

    $course = Course::factory()->create([
        'center_id' => $center->id,
        'access_model' => CourseAccessModel::VideoCode,
    ]);
    $video = Video::factory()->create([
        'center_id' => $center->id,
    ]);
    attachVideoToCourseForVideoCodeBatches($course, $video);

    $this->asCenterAdmin($center);

    $response = $this->postJson(
        "/api/v1/admin/centers/{$center->id}/courses/{$course->id}/videos/{$video->id}/code-batches",
        [
            'quantity' => 60,
        ],
        $this->adminHeaders()
    );

    $response->assertStatus(422)
        ->assertJsonPath('error.code', 'VALIDATION_ERROR')
        ->assertJsonPath('error.details.quantity.0', 'The quantity field must not be greater than 50.');
});

it('returns not found when the video is not attached to the specified course', function (): void {
    $center = Center::factory()->create();
    $course = Course::factory()->create([
        'center_id' => $center->id,
        'access_model' => CourseAccessModel::VideoCode,
    ]);
    $video = Video::factory()->create([
        'center_id' => $center->id,
    ]);

    $this->asCenterAdmin($center);

    $response = $this->postJson(
        "/api/v1/admin/centers/{$center->id}/courses/{$course->id}/videos/{$video->id}/code-batches",
        [
            'quantity' => 10,
        ],
        $this->adminHeaders()
    );

    $response->assertNotFound()
        ->assertJsonPath('error.code', 'NOT_FOUND')
        ->assertJsonPath('error.message', 'Video not found in this course.');
});

it('blocks creating a batch for enrollment-based courses', function (): void {
    $center = Center::factory()->create();
    $course = Course::factory()->create([
        'center_id' => $center->id,
        'access_model' => CourseAccessModel::Enrollment,
    ]);
    $video = Video::factory()->create([
        'center_id' => $center->id,
    ]);
    attachVideoToCourseForVideoCodeBatches($course, $video);

    $this->asCenterAdmin($center);

    $response = $this->postJson(
        "/api/v1/admin/centers/{$center->id}/courses/{$course->id}/videos/{$video->id}/code-batches",
        [
            'quantity' => 10,
        ],
        $this->adminHeaders()
    );

    $response->assertStatus(422)
        ->assertJsonPath('error.code', 'INVALID_STATE')
        ->assertJsonPath(
            'error.message',
            'Video code batches can only be created for courses using the video_code access model.'
        );
});

it('allows creating another open batch for the same video in a different course', function (): void {
    $center = Center::factory()->create();
    $courseA = Course::factory()->create([
        'center_id' => $center->id,
        'access_model' => CourseAccessModel::VideoCode,
    ]);
    $courseB = Course::factory()->create([
        'center_id' => $center->id,
        'access_model' => CourseAccessModel::VideoCode,
    ]);
    $video = Video::factory()->create([
        'center_id' => $center->id,
    ]);

    attachVideoToCourseForVideoCodeBatches($courseA, $video);
    attachVideoToCourseForVideoCodeBatches($courseB, $video);

    $admin = $this->asCenterAdmin($center);

    VideoCodeBatch::factory()->create([
        'course_id' => $courseA->id,
        'video_id' => $video->id,
        'center_id' => $center->id,
        'generated_by' => $admin->id,
    ]);

    $response = $this->postJson(
        "/api/v1/admin/centers/{$center->id}/courses/{$courseB->id}/videos/{$video->id}/code-batches",
        [
            'quantity' => 10,
        ],
        $this->adminHeaders()
    );

    $response->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.course_id', $courseB->id)
        ->assertJsonPath('data.video_id', $video->id);
});

it('blocks creating another open batch for the same video in the same course', function (): void {
    $center = Center::factory()->create();
    $course = Course::factory()->create([
        'center_id' => $center->id,
        'access_model' => CourseAccessModel::VideoCode,
    ]);
    $video = Video::factory()->create([
        'center_id' => $center->id,
    ]);

    attachVideoToCourseForVideoCodeBatches($course, $video);

    $admin = $this->asCenterAdmin($center);

    VideoCodeBatch::factory()->create([
        'course_id' => $course->id,
        'video_id' => $video->id,
        'center_id' => $center->id,
        'generated_by' => $admin->id,
    ]);

    $response = $this->postJson(
        "/api/v1/admin/centers/{$center->id}/courses/{$course->id}/videos/{$video->id}/code-batches",
        [
            'quantity' => 10,
        ],
        $this->adminHeaders()
    );

    $response->assertStatus(422)
        ->assertJsonPath('error.code', 'VIDEO_CODE_BATCH_ACTIVE_EXISTS');
});

it('allows increasing sold limit after a batch is already closed', function (): void {
    $center = Center::factory()->create();
    $course = Course::factory()->create([
        'center_id' => $center->id,
        'access_model' => CourseAccessModel::VideoCode,
    ]);
    $video = Video::factory()->create([
        'center_id' => $center->id,
    ]);

    attachVideoToCourseForVideoCodeBatches($course, $video);

    $admin = $this->asCenterAdmin($center);

    $batch = VideoCodeBatch::factory()->closed(40)->create([
        'course_id' => $course->id,
        'video_id' => $video->id,
        'center_id' => $center->id,
        'generated_by' => $admin->id,
        'redeemed_count' => 25,
        'quantity' => 100,
    ]);

    $response = $this->postJson(
        "/api/v1/admin/centers/{$center->id}/code-batches/{$batch->id}/close",
        [
            'sold_limit' => 60,
        ],
        $this->adminHeaders()
    );

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Video code batch sold limit updated successfully')
        ->assertJsonPath('data.sold_limit', 60)
        ->assertJsonPath('data.can_close', true)
        ->assertJsonPath('data.remaining_redemptions', 35)
        ->assertJsonPath('data.invoice_amount_codes', 60);

    $this->assertDatabaseHas('video_code_batches', [
        'id' => $batch->id,
        'sold_limit' => 60,
        'redeemed_count' => 25,
    ]);
});

it('rejects reducing sold limit on a closed batch', function (): void {
    $center = Center::factory()->create();
    $course = Course::factory()->create([
        'center_id' => $center->id,
        'access_model' => CourseAccessModel::VideoCode,
    ]);
    $video = Video::factory()->create([
        'center_id' => $center->id,
    ]);

    attachVideoToCourseForVideoCodeBatches($course, $video);

    $admin = $this->asCenterAdmin($center);

    $batch = VideoCodeBatch::factory()->closed(40)->create([
        'course_id' => $course->id,
        'video_id' => $video->id,
        'center_id' => $center->id,
        'generated_by' => $admin->id,
        'redeemed_count' => 25,
        'quantity' => 100,
    ]);

    $response = $this->postJson(
        "/api/v1/admin/centers/{$center->id}/code-batches/{$batch->id}/close",
        [
            'sold_limit' => 35,
        ],
        $this->adminHeaders()
    );

    $response->assertStatus(422)
        ->assertJsonPath('error.code', 'INVALID_STATE')
        ->assertJsonPath('error.message', 'Closed batch sold limit can only be increased.');
});

it('keeps can_close true in list responses for closed batches with remaining sellable capacity', function (): void {
    $center = Center::factory()->create();
    CenterSetting::factory()->create([
        'center_id' => $center->id,
        'settings' => [
            'video_code_batch_max_quantity' => 100,
            'video_code_batch_default_view_limit' => 6,
        ],
    ]);
    $course = Course::factory()->create([
        'center_id' => $center->id,
        'access_model' => CourseAccessModel::VideoCode,
    ]);
    $video = Video::factory()->create([
        'center_id' => $center->id,
    ]);

    attachVideoToCourseForVideoCodeBatches($course, $video);

    $admin = $this->asCenterAdmin($center);

    $batch = VideoCodeBatch::factory()->closed(40)->create([
        'course_id' => $course->id,
        'video_id' => $video->id,
        'center_id' => $center->id,
        'generated_by' => $admin->id,
        'redeemed_count' => 25,
        'quantity' => 100,
    ]);

    $response = $this->getJson(
        "/api/v1/admin/centers/{$center->id}/video-code-batches?course_id={$course->id}",
        $this->adminHeaders()
    );

    $response->assertOk()
        ->assertJsonPath('data.0.id', $batch->id)
        ->assertJsonPath('data.0.available_codes', 75)
        ->assertJsonPath('data.0.remaining_redemptions', 15)
        ->assertJsonPath('data.0.can_close', true)
        ->assertJsonPath('settings.max_quantity', 100)
        ->assertJsonPath('settings.default_view_limit', 6);
});

it('returns resolved video code batch settings in list responses', function (): void {
    $center = Center::factory()->create();
    CenterSetting::factory()->create([
        'center_id' => $center->id,
        'settings' => [
            'video_code_batch_max_quantity' => 100,
            'video_code_batch_default_view_limit' => 6,
        ],
    ]);
    SystemSetting::factory()->create([
        'key' => 'max_video_code_batch_quantity',
        'value' => ['value' => 120],
        'is_public' => false,
    ]);
    SystemSetting::factory()->create([
        'key' => 'max_video_code_batch_view_limit',
        'value' => ['value' => 8],
        'is_public' => false,
    ]);

    $this->asCenterAdmin($center);

    $response = $this->getJson(
        "/api/v1/admin/centers/{$center->id}/video-code-batches",
        $this->adminHeaders()
    );

    $response->assertOk()
        ->assertJsonPath('settings.max_quantity', 100)
        ->assertJsonPath('settings.default_view_limit', 6);
});

it('rejects expanding a batch beyond the center max quantity setting', function (): void {
    $center = Center::factory()->create();
    CenterSetting::factory()->create([
        'center_id' => $center->id,
        'settings' => [
            'video_code_batch_max_quantity' => 50,
        ],
    ]);

    $course = Course::factory()->create([
        'center_id' => $center->id,
        'access_model' => CourseAccessModel::VideoCode,
    ]);
    $video = Video::factory()->create([
        'center_id' => $center->id,
    ]);
    attachVideoToCourseForVideoCodeBatches($course, $video);

    $admin = $this->asCenterAdmin($center);

    $batch = VideoCodeBatch::factory()->create([
        'course_id' => $course->id,
        'video_id' => $video->id,
        'center_id' => $center->id,
        'generated_by' => $admin->id,
        'quantity' => 40,
    ]);

    $response = $this->postJson(
        "/api/v1/admin/centers/{$center->id}/code-batches/{$batch->id}/expand",
        [
            'additional_quantity' => 11,
        ],
        $this->adminHeaders()
    );

    $response->assertStatus(422)
        ->assertJsonPath('error.code', 'VALIDATION_ERROR')
        ->assertJsonPath('error.details.additional_quantity.0', 'The additional quantity field must not be greater than 10.');
});

it('returns expanded statistics payload for dashboard batch details', function (): void {
    $center = Center::factory()->create();
    $course = Course::factory()->create([
        'center_id' => $center->id,
        'access_model' => CourseAccessModel::VideoCode,
    ]);
    $video = Video::factory()->create([
        'center_id' => $center->id,
    ]);

    attachVideoToCourseForVideoCodeBatches($course, $video);

    $admin = $this->asCenterAdmin($center);

    $batch = VideoCodeBatch::factory()->closed(4)->create([
        'course_id' => $course->id,
        'video_id' => $video->id,
        'center_id' => $center->id,
        'generated_by' => $admin->id,
        'redeemed_count' => 2,
        'quantity' => 5,
    ]);

    $student = User::factory()->student()->create([
        'center_id' => $center->id,
        'name' => 'John Doe',
        'phone' => '1234567890',
        'country_code' => '+1',
    ]);

    $access = VideoAccess::query()->create([
        'user_id' => $student->id,
        'video_id' => $video->id,
        'course_id' => $course->id,
        'center_id' => $center->id,
        'enrollment_id' => null,
        'video_access_code_id' => null,
        'total_view_limit' => $batch->view_limit_per_code,
        'granted_at' => now(),
    ]);

    Carbon::setTestNow('2026-03-14 12:00:00');
    VideoCodeRedemption::query()->create([
        'batch_id' => $batch->id,
        'sequence_number' => 2,
        'user_id' => $student->id,
        'video_access_id' => $access->id,
        'redeemed_at' => now(),
    ]);

    Carbon::setTestNow('2026-03-14 14:30:00');
    VideoCodeRedemption::query()->create([
        'batch_id' => $batch->id,
        'sequence_number' => 4,
        'user_id' => $student->id,
        'video_access_id' => $access->id,
        'redeemed_at' => now(),
    ]);
    Carbon::setTestNow();

    $this->get(
        "/api/v1/admin/centers/{$center->id}/code-batches/{$batch->id}/export/csv",
        $this->adminHeaders()
    )->assertOk();

    $code = app(VideoCodeGenerator::class)->generateCode($batch->fresh() ?? $batch, 4);

    $response = $this->getJson(
        "/api/v1/admin/centers/{$center->id}/code-batches/{$batch->id}/statistics",
        $this->adminHeaders()
    );

    $response->assertOk()
        ->assertJsonPath('data.batch_id', $batch->id)
        ->assertJsonPath('data.batch_code', $batch->batch_code)
        ->assertJsonPath('data.total_codes', 5)
        ->assertJsonPath('data.quantity', 5)
        ->assertJsonPath('data.available_count', 3)
        ->assertJsonPath('data.sold_limit', 4)
        ->assertJsonPath('data.status', 'closed')
        ->assertJsonPath('data.first_redemption_at', '2026-03-14T12:00:00+00:00')
        ->assertJsonPath('data.last_redemption_at', '2026-03-14T14:30:00+00:00')
        ->assertJsonPath('data.exports.0.format', 'csv')
        ->assertJsonPath('data.exports.0.exported_by.id', $admin->id)
        ->assertJsonPath('data.recent_redemptions.0.code', $code)
        ->assertJsonPath('data.recent_redemptions.0.user.name', 'John Doe')
        ->assertJsonPath('data.recent_redemptions.0.user.phone', '+11234567890');
});

it('lists paginated batch redemptions for batch details', function (): void {
    $center = Center::factory()->create();
    $course = Course::factory()->create([
        'center_id' => $center->id,
        'access_model' => CourseAccessModel::VideoCode,
    ]);
    $video = Video::factory()->create([
        'center_id' => $center->id,
    ]);

    attachVideoToCourseForVideoCodeBatches($course, $video);

    $admin = $this->asCenterAdmin($center);

    $batch = VideoCodeBatch::factory()->closed(10)->create([
        'course_id' => $course->id,
        'video_id' => $video->id,
        'center_id' => $center->id,
        'generated_by' => $admin->id,
        'redeemed_count' => 2,
        'quantity' => 10,
    ]);

    $studentA = User::factory()->student()->create([
        'center_id' => $center->id,
        'name' => 'First Student',
        'phone' => '1111111111',
        'country_code' => '+20',
    ]);
    $studentB = User::factory()->student()->create([
        'center_id' => $center->id,
        'name' => 'Second Student',
        'phone' => '2222222222',
        'country_code' => '+1',
    ]);

    $accessA = VideoAccess::query()->create([
        'user_id' => $studentA->id,
        'video_id' => $video->id,
        'course_id' => $course->id,
        'center_id' => $center->id,
        'enrollment_id' => null,
        'video_access_code_id' => null,
        'total_view_limit' => $batch->view_limit_per_code,
        'granted_at' => now(),
    ]);
    $accessB = VideoAccess::query()->create([
        'user_id' => $studentB->id,
        'video_id' => $video->id,
        'course_id' => $course->id,
        'center_id' => $center->id,
        'enrollment_id' => null,
        'video_access_code_id' => null,
        'total_view_limit' => $batch->view_limit_per_code,
        'granted_at' => now(),
    ]);

    Carbon::setTestNow('2026-03-14 12:00:00');
    VideoCodeRedemption::query()->create([
        'batch_id' => $batch->id,
        'sequence_number' => 2,
        'user_id' => $studentA->id,
        'video_access_id' => $accessA->id,
        'redeemed_at' => now(),
    ]);

    Carbon::setTestNow('2026-03-14 14:30:00');
    VideoCodeRedemption::query()->create([
        'batch_id' => $batch->id,
        'sequence_number' => 4,
        'user_id' => $studentB->id,
        'video_access_id' => $accessB->id,
        'redeemed_at' => now(),
    ]);
    Carbon::setTestNow();

    $olderCode = app(VideoCodeGenerator::class)->generateCode($batch->fresh() ?? $batch, 2);

    $response = $this->getJson(
        "/api/v1/admin/centers/{$center->id}/code-batches/{$batch->id}/redemptions?per_page=1&page=2",
        $this->adminHeaders()
    );

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.sequence_number', 2)
        ->assertJsonPath('data.0.code', $olderCode)
        ->assertJsonPath('data.0.user.name', 'First Student')
        ->assertJsonPath('data.0.user.phone', '+201111111111')
        ->assertJsonPath('meta.page', 2)
        ->assertJsonPath('meta.current_page', 2)
        ->assertJsonPath('meta.last_page', 2)
        ->assertJsonPath('meta.per_page', 1)
        ->assertJsonPath('meta.total', 2);
});

it('searches batch redemptions by generated code', function (): void {
    $center = Center::factory()->create();
    $course = Course::factory()->create([
        'center_id' => $center->id,
        'access_model' => CourseAccessModel::VideoCode,
    ]);
    $video = Video::factory()->create([
        'center_id' => $center->id,
    ]);

    attachVideoToCourseForVideoCodeBatches($course, $video);

    $admin = $this->asCenterAdmin($center);

    $batch = VideoCodeBatch::factory()->closed(10)->create([
        'course_id' => $course->id,
        'video_id' => $video->id,
        'center_id' => $center->id,
        'generated_by' => $admin->id,
        'redeemed_count' => 2,
        'quantity' => 10,
    ]);

    $student = User::factory()->student()->create([
        'center_id' => $center->id,
        'name' => 'Lookup Student',
    ]);

    $access = VideoAccess::query()->create([
        'user_id' => $student->id,
        'video_id' => $video->id,
        'course_id' => $course->id,
        'center_id' => $center->id,
        'enrollment_id' => null,
        'video_access_code_id' => null,
        'total_view_limit' => $batch->view_limit_per_code,
        'granted_at' => now(),
    ]);

    VideoCodeRedemption::query()->create([
        'batch_id' => $batch->id,
        'sequence_number' => 3,
        'user_id' => $student->id,
        'video_access_id' => $access->id,
        'redeemed_at' => now(),
    ]);
    VideoCodeRedemption::query()->create([
        'batch_id' => $batch->id,
        'sequence_number' => 7,
        'user_id' => $student->id,
        'video_access_id' => $access->id,
        'redeemed_at' => now()->subMinute(),
    ]);

    $code = app(VideoCodeGenerator::class)->generateCode($batch->fresh() ?? $batch, 3);

    $response = $this->getJson(
        "/api/v1/admin/centers/{$center->id}/code-batches/{$batch->id}/redemptions?search={$code}",
        $this->adminHeaders()
    );

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.sequence_number', 3)
        ->assertJsonPath('data.0.code', $code)
        ->assertJsonPath('meta.total', 1);
});

it('searches batch listings by batch code and related titles', function (): void {
    $center = Center::factory()->create();
    $coursePhysics = Course::factory()->create([
        'center_id' => $center->id,
        'access_model' => CourseAccessModel::VideoCode,
        'title_translations' => ['en' => 'Physics Elite'],
    ]);
    $courseChemistry = Course::factory()->create([
        'center_id' => $center->id,
        'access_model' => CourseAccessModel::VideoCode,
        'title_translations' => ['en' => 'Chemistry Base'],
    ]);
    $videoPhysics = Video::factory()->create([
        'center_id' => $center->id,
        'title_translations' => ['en' => 'Physics Motion'],
    ]);
    $videoChemistry = Video::factory()->create([
        'center_id' => $center->id,
        'title_translations' => ['en' => 'Chemistry Bonds'],
    ]);

    attachVideoToCourseForVideoCodeBatches($coursePhysics, $videoPhysics);
    attachVideoToCourseForVideoCodeBatches($courseChemistry, $videoChemistry);

    $admin = $this->asCenterAdmin($center);

    $physicsBatch = VideoCodeBatch::factory()->create([
        'course_id' => $coursePhysics->id,
        'video_id' => $videoPhysics->id,
        'center_id' => $center->id,
        'generated_by' => $admin->id,
        'batch_code' => 'PHYS',
    ]);
    VideoCodeBatch::factory()->create([
        'course_id' => $courseChemistry->id,
        'video_id' => $videoChemistry->id,
        'center_id' => $center->id,
        'generated_by' => $admin->id,
        'batch_code' => 'CHEM',
    ]);

    $byTitle = $this->getJson(
        "/api/v1/admin/centers/{$center->id}/video-code-batches?search=Physics",
        $this->adminHeaders()
    );

    $byTitle->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $physicsBatch->id)
        ->assertJsonPath('meta.total', 1);

    $byBatchCode = $this->getJson(
        "/api/v1/admin/centers/{$center->id}/video-code-batches?search=PHYS",
        $this->adminHeaders()
    );

    $byBatchCode->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $physicsBatch->id)
        ->assertJsonPath('meta.total', 1);
});

it('exports a video code batch as a real pdf', function (): void {
    $center = Center::factory()->create();
    $course = Course::factory()->create([
        'center_id' => $center->id,
        'access_model' => CourseAccessModel::VideoCode,
    ]);
    $video = Video::factory()->create([
        'center_id' => $center->id,
        'title_translations' => ['en' => 'Physics Motion'],
    ]);

    attachVideoToCourseForVideoCodeBatches($course, $video);

    $admin = $this->asCenterAdmin($center);

    $batch = VideoCodeBatch::factory()->create([
        'course_id' => $course->id,
        'video_id' => $video->id,
        'center_id' => $center->id,
        'generated_by' => $admin->id,
        'quantity' => 2,
    ]);

    $response = $this->get(
        "/api/v1/admin/centers/{$center->id}/code-batches/{$batch->id}/export/pdf?cards_per_page=4",
        $this->adminHeaders()
    );

    $response->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    expect($response->headers->get('content-disposition'))
        ->toContain(sprintf('%s-%s.pdf', Str::slug('Physics Motion'), $batch->batch_code));

    expect($response->streamedContent())->toStartWith('%PDF-');
    expect(data_get($batch->fresh()?->metadata, 'exports.0.format'))->toBe('pdf');
});

it('exports a 100-code pdf batch with the default printable layout', function (): void {
    $center = Center::factory()->create();
    $course = Course::factory()->create([
        'center_id' => $center->id,
        'access_model' => CourseAccessModel::VideoCode,
    ]);
    $video = Video::factory()->create([
        'center_id' => $center->id,
        'title_translations' => ['en' => 'Physics Motion'],
    ]);

    attachVideoToCourseForVideoCodeBatches($course, $video);

    $admin = $this->asCenterAdmin($center);

    $batch = VideoCodeBatch::factory()->create([
        'course_id' => $course->id,
        'video_id' => $video->id,
        'center_id' => $center->id,
        'generated_by' => $admin->id,
        'quantity' => 100,
    ]);

    $response = $this->get(
        "/api/v1/admin/centers/{$center->id}/code-batches/{$batch->id}/export/pdf",
        $this->adminHeaders()
    );

    $response->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    expect($response->streamedContent())->toStartWith('%PDF-');
    expect(data_get($batch->fresh()?->metadata, 'exports.0.count'))->toBe(100);
});

it('rejects invalid cards_per_page for pdf export', function (): void {
    $center = Center::factory()->create();
    $course = Course::factory()->create([
        'center_id' => $center->id,
        'access_model' => CourseAccessModel::VideoCode,
    ]);
    $video = Video::factory()->create([
        'center_id' => $center->id,
    ]);

    attachVideoToCourseForVideoCodeBatches($course, $video);

    $admin = $this->asCenterAdmin($center);

    $batch = VideoCodeBatch::factory()->create([
        'course_id' => $course->id,
        'video_id' => $video->id,
        'center_id' => $center->id,
        'generated_by' => $admin->id,
        'quantity' => 2,
    ]);

    $response = $this->get(
        "/api/v1/admin/centers/{$center->id}/code-batches/{$batch->id}/export/pdf?cards_per_page=5",
        $this->adminHeaders()
    );

    $response->assertStatus(422);
});

it('sends a batch csv export to whatsapp and records the delivery in history', function (): void {
    config(['evolution.otp_instance_name' => 'otp-instance']);

    $center = Center::factory()->create();
    $course = Course::factory()->create([
        'center_id' => $center->id,
        'access_model' => CourseAccessModel::VideoCode,
    ]);
    $video = Video::factory()->create([
        'center_id' => $center->id,
        'title_translations' => ['en' => 'Physics Motion'],
    ]);

    attachVideoToCourseForVideoCodeBatches($course, $video);

    $admin = $this->asCenterAdmin($center);

    $batch = VideoCodeBatch::factory()->create([
        'course_id' => $course->id,
        'video_id' => $video->id,
        'center_id' => $center->id,
        'generated_by' => $admin->id,
        'quantity' => 5,
    ]);

    $this->mock(EvolutionApiClient::class)
        ->shouldReceive('sendMedia')
        ->once()
        ->with('otp-instance', \Mockery::on(function (array $payload) use ($batch): bool {
            $media = $payload['media'] ?? null;
            $decoded = is_string($media) ? base64_decode($media, true) : false;

            return ($payload['number'] ?? null) === '201001234567'
                && ($payload['mediatype'] ?? null) === 'document'
                && ($payload['mimetype'] ?? null) === 'text/csv'
                && ($payload['fileName'] ?? null) === sprintf('physics-motion-%s.csv', $batch->batch_code)
                && is_string($decoded)
                && str_contains($decoded, 'Sequence,Code,Video,Course')
                && str_contains($decoded, 'Physics Motion');
        }))
        ->andReturn(['key' => ['id' => 'msg-batch-1']]);

    $response = $this->postJson(
        "/api/v1/admin/centers/{$center->id}/code-batches/{$batch->id}/send-whatsapp-csv",
        [
            'phone_number' => '+20 100 123 4567',
            'start_sequence' => 2,
            'end_sequence' => 4,
        ],
        $this->adminHeaders()
    );

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'WhatsApp CSV sent successfully.')
        ->assertJsonPath('data.type', 'whatsapp_csv')
        ->assertJsonPath('data.format', 'csv')
        ->assertJsonPath('data.delivery_channel', 'whatsapp')
        ->assertJsonPath('data.status', 'sent')
        ->assertJsonPath('data.destination_masked', '+20100****567')
        ->assertJsonPath('data.code_range', '2-4')
        ->assertJsonPath('data.count', 3)
        ->assertJsonPath('data.file_name', sprintf('physics-motion-%s.csv', $batch->batch_code));

    $exports = data_get($batch->fresh()?->metadata, 'exports');

    expect($exports)->toHaveCount(1)
        ->and(data_get($exports, '0.status'))->toBe('sent')
        ->and(data_get($exports, '0.delivery_channel'))->toBe('whatsapp')
        ->and(data_get($exports, '0.destination_masked'))->toBe('+20100****567')
        ->and(data_get($exports, '0.start_sequence'))->toBe(2)
        ->and(data_get($exports, '0.end_sequence'))->toBe(4);
});

it('returns a failed whatsapp csv send immediately when the sync queue send fails', function (): void {
    config(['evolution.otp_instance_name' => 'otp-instance']);

    $center = Center::factory()->create();
    $course = Course::factory()->create([
        'center_id' => $center->id,
        'access_model' => CourseAccessModel::VideoCode,
    ]);
    $video = Video::factory()->create([
        'center_id' => $center->id,
    ]);

    attachVideoToCourseForVideoCodeBatches($course, $video);

    $admin = $this->asCenterAdmin($center);

    $batch = VideoCodeBatch::factory()->create([
        'course_id' => $course->id,
        'video_id' => $video->id,
        'center_id' => $center->id,
        'generated_by' => $admin->id,
        'quantity' => 5,
    ]);

    $this->mock(EvolutionApiClient::class)
        ->shouldReceive('sendMedia')
        ->once()
        ->andThrow(new \RuntimeException('Document send failed.'));

    $response = $this->postJson(
        "/api/v1/admin/centers/{$center->id}/code-batches/{$batch->id}/send-whatsapp-csv",
        [
            'phone_number' => '+201001234567',
        ],
        $this->adminHeaders()
    );

    $response->assertStatus(500)
        ->assertJsonPath('success', false)
        ->assertJsonPath('error.code', 'WHATSAPP_SEND_FAILED')
        ->assertJsonPath('error.message', 'Failed to send WhatsApp message: Document send failed.');

    expect(data_get($batch->fresh()?->metadata, 'exports.0.status'))->toBe('failed')
        ->and(data_get($batch->fresh()?->metadata, 'exports.0.error'))->toBe('Failed to send WhatsApp message: Document send failed.');
});

it('appends export history across repeated exports', function (): void {
    $center = Center::factory()->create();
    $course = Course::factory()->create([
        'center_id' => $center->id,
        'access_model' => CourseAccessModel::VideoCode,
    ]);
    $video = Video::factory()->create([
        'center_id' => $center->id,
    ]);

    attachVideoToCourseForVideoCodeBatches($course, $video);

    $admin = $this->asCenterAdmin($center);

    $batch = VideoCodeBatch::factory()->create([
        'course_id' => $course->id,
        'video_id' => $video->id,
        'center_id' => $center->id,
        'generated_by' => $admin->id,
        'quantity' => 5,
    ]);

    $csvResponse = $this->get(
        "/api/v1/admin/centers/{$center->id}/code-batches/{$batch->id}/export/csv",
        $this->adminHeaders()
    );

    $csvResponse->assertOk();

    expect($csvResponse->headers->get('content-disposition'))
        ->toContain(sprintf('%s-%s.csv', Str::slug($video->translate('title')), $batch->batch_code));

    $this->get(
        "/api/v1/admin/centers/{$center->id}/code-batches/{$batch->id}/export/pdf",
        $this->adminHeaders()
    )->assertOk();

    $exports = data_get($batch->fresh()?->metadata, 'exports');

    expect($exports)->toHaveCount(2)
        ->and(data_get($exports, '0.format'))->toBe('csv')
        ->and(data_get($exports, '0.exported_by.id'))->toBe($admin->id)
        ->and(data_get($exports, '1.format'))->toBe('pdf')
        ->and(data_get($exports, '1.exported_by.id'))->toBe($admin->id);
});
