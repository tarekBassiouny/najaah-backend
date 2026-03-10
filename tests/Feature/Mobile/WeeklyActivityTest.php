<?php

declare(strict_types=1);

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Center;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\PlaybackSession;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Models\UserDevice;
use App\Models\Video;
use App\Models\VideoUploadSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\ApiTestHelper;

uses(RefreshDatabase::class, ApiTestHelper::class)->group('mobile', 'activity');

it('returns empty weekly activity series for student with no activity', function (): void {
    $center = Center::factory()->create(['type' => 1, 'api_key' => 'center-weekly-empty']);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);
    $student->centers()->syncWithoutDetaching([$center->id => ['type' => 'student']]);

    $this->asApiUser($student);

    $response = $this->apiGet(
        "/api/v1/centers/{$center->id}/activity/weekly?days=7&timezone=UTC",
        ['X-Api-Key' => $center->api_key]
    );

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(7, 'data.series')
        ->assertJsonPath('data.totals.watch_duration_seconds', 0)
        ->assertJsonPath('data.totals.quiz_attempts_count', 0)
        ->assertJsonPath('data.totals.assignment_submissions_count', 0);
});

it('returns aggregated weekly activity counts and totals', function (): void {
    $center = Center::factory()->create(['type' => 1, 'api_key' => 'center-weekly-data']);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);
    $student->centers()->syncWithoutDetaching([$center->id => ['type' => 'student']]);

    $course = Course::factory()->create([
        'center_id' => $center->id,
        'status' => 3,
        'is_published' => true,
    ]);

    $enrollment = Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'center_id' => $center->id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    $uploadSession = VideoUploadSession::factory()->create([
        'center_id' => $center->id,
        'upload_status' => 3,
    ]);
    $video = Video::factory()->create([
        'center_id' => $center->id,
        'upload_session_id' => $uploadSession->id,
        'encoding_status' => 3,
        'lifecycle_status' => 2,
    ]);
    $device = UserDevice::factory()->create(['user_id' => $student->id]);

    $activityDay = now('UTC')->subDay()->setTime(12, 0);
    $activityDate = $activityDay->toDateString();

    PlaybackSession::factory()->create([
        'user_id' => $student->id,
        'video_id' => $video->id,
        'course_id' => $course->id,
        'device_id' => $device->id,
        'started_at' => $activityDay->copy()->subMinutes(20),
        'ended_at' => $activityDay->copy()->subMinutes(10),
        'watch_duration' => 120,
        'is_full_play' => true,
    ]);

    PlaybackSession::factory()->create([
        'user_id' => $student->id,
        'video_id' => $video->id,
        'course_id' => $course->id,
        'device_id' => $device->id,
        'started_at' => $activityDay->copy()->subMinutes(5),
        'ended_at' => $activityDay->copy()->addMinutes(5),
        'watch_duration' => 30,
        'is_full_play' => false,
    ]);

    $quiz = Quiz::factory()->forCourse($course)->active()->create();
    QuizAttempt::factory()->create([
        'quiz_id' => $quiz->id,
        'user_id' => $student->id,
        'enrollment_id' => $enrollment->id,
        'center_id' => $center->id,
        'attempt_number' => 1,
        'started_at' => $activityDay->copy(),
        'created_at' => $activityDay->copy(),
    ]);

    $assignment = Assignment::factory()->forCourse($course)->active()->create();
    AssignmentSubmission::factory()->submitted()->create([
        'assignment_id' => $assignment->id,
        'user_id' => $student->id,
        'enrollment_id' => $enrollment->id,
        'center_id' => $center->id,
        'submitted_at' => $activityDay->copy()->addHour(),
    ]);

    $this->asApiUser($student);

    $response = $this->apiGet(
        "/api/v1/centers/{$center->id}/activity/weekly?days=3&timezone=UTC",
        ['X-Api-Key' => $center->api_key]
    );

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.range.days', 3)
        ->assertJsonPath('data.range.timezone', 'UTC')
        ->assertJsonPath('data.totals.watch_duration_seconds', 150)
        ->assertJsonPath('data.totals.quiz_attempts_count', 1)
        ->assertJsonPath('data.totals.assignment_submissions_count', 1);

    $series = collect($response->json('data.series'));
    $dayEntry = $series->firstWhere('date', $activityDate);

    expect($dayEntry)->toBeArray()
        ->and($dayEntry['watch_duration_seconds'])->toBe(150)
        ->and($dayEntry['quiz_attempts_count'])->toBe(1)
        ->and($dayEntry['assignment_submissions_count'])->toBe(1);
});

it('returns center mismatch for weekly activity when student is out of scope', function (): void {
    $centerA = Center::factory()->create(['type' => 1, 'api_key' => 'center-weekly-a']);
    $centerB = Center::factory()->create(['type' => 1, 'api_key' => 'center-weekly-b']);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $centerA->id,
    ]);
    $student->centers()->syncWithoutDetaching([$centerA->id => ['type' => 'student']]);

    $this->asApiUser($student);

    $response = $this->apiGet(
        "/api/v1/centers/{$centerB->id}/activity/weekly",
        ['X-Api-Key' => $centerB->api_key]
    );

    $response->assertStatus(403)
        ->assertJsonPath('success', false)
        ->assertJsonPath('error.code', 'CENTER_MISMATCH');
});
