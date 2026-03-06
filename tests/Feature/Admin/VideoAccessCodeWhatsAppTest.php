<?php

declare(strict_types=1);

use App\Enums\VideoAccessCodeStatus;
use App\Models\Center;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoAccessCode;
use App\Services\Evolution\EvolutionApiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\AdminTestHelper;

uses(RefreshDatabase::class, AdminTestHelper::class)->group('video-access', 'admin', 'whatsapp');

it('sends video access code via whatsapp using country code plus phone', function (): void {
    config(['evolution.otp_instance_name' => 'otp-instance']);

    $center = Center::factory()->create();
    $admin = $this->asCenterAdmin($center);

    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
        'country_code' => '+20',
        'phone' => '1012345678',
    ]);

    $course = Course::factory()->create([
        'center_id' => $center->id,
    ]);

    $video = Video::factory()->create([
        'center_id' => $center->id,
        'created_by' => $admin->id,
    ]);

    $enrollment = Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'center_id' => $center->id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    $code = VideoAccessCode::query()->create([
        'user_id' => $student->id,
        'video_id' => $video->id,
        'course_id' => $course->id,
        'center_id' => $center->id,
        'enrollment_id' => $enrollment->id,
        'video_access_request_id' => null,
        'code' => 'AB12CD34',
        'status' => VideoAccessCodeStatus::Active,
        'generated_by' => $admin->id,
        'generated_at' => now(),
        'expires_at' => now()->addDays(30),
    ]);

    $this->mock(EvolutionApiClient::class)
        ->shouldReceive('sendText')
        ->once()
        ->with('otp-instance', \Mockery::on(function (array $payload) use ($code): bool {
            return ($payload['number'] ?? null) === '201012345678'
                && is_string($payload['text'] ?? null)
                && str_contains((string) $payload['text'], $code->code);
        }))
        ->andReturn(['key' => ['id' => 'msg-1']]);

    $response = $this->postJson(
        "/api/v1/admin/centers/{$center->id}/video-access-codes/{$code->id}/send-whatsapp",
        ['format' => 'text_code'],
        $this->adminHeaders()
    );

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Code sent to student via WhatsApp');
});

it('normalizes 00-prefixed country codes before sending via whatsapp', function (): void {
    config(['evolution.otp_instance_name' => 'otp-instance']);

    $center = Center::factory()->create();
    $admin = $this->asCenterAdmin($center);

    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
        'country_code' => '0020',
        'phone' => '1012345678',
    ]);

    $course = Course::factory()->create([
        'center_id' => $center->id,
    ]);

    $video = Video::factory()->create([
        'center_id' => $center->id,
        'created_by' => $admin->id,
    ]);

    $enrollment = Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'center_id' => $center->id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    $code = VideoAccessCode::query()->create([
        'user_id' => $student->id,
        'video_id' => $video->id,
        'course_id' => $course->id,
        'center_id' => $center->id,
        'enrollment_id' => $enrollment->id,
        'video_access_request_id' => null,
        'code' => 'AB12CD34',
        'status' => VideoAccessCodeStatus::Active,
        'generated_by' => $admin->id,
        'generated_at' => now(),
        'expires_at' => now()->addDays(30),
    ]);

    $this->mock(EvolutionApiClient::class)
        ->shouldReceive('sendText')
        ->once()
        ->with('otp-instance', \Mockery::on(function (array $payload) use ($code): bool {
            return ($payload['number'] ?? null) === '201012345678'
                && is_string($payload['text'] ?? null)
                && str_contains((string) $payload['text'], $code->code);
        }))
        ->andReturn(['key' => ['id' => 'msg-2']]);

    $response = $this->postJson(
        "/api/v1/admin/centers/{$center->id}/video-access-codes/{$code->id}/send-whatsapp",
        ['format' => 'text_code'],
        $this->adminHeaders()
    );

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Code sent to student via WhatsApp');
});

it('returns actionable error when evolution instance is disconnected', function (): void {
    config(['evolution.otp_instance_name' => 'otp-instance']);

    $center = Center::factory()->create();
    $admin = $this->asCenterAdmin($center);

    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
        'country_code' => '+20',
        'phone' => '1012345678',
    ]);

    $course = Course::factory()->create([
        'center_id' => $center->id,
    ]);

    $video = Video::factory()->create([
        'center_id' => $center->id,
        'created_by' => $admin->id,
    ]);

    $enrollment = Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'center_id' => $center->id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    $code = VideoAccessCode::query()->create([
        'user_id' => $student->id,
        'video_id' => $video->id,
        'course_id' => $course->id,
        'center_id' => $center->id,
        'enrollment_id' => $enrollment->id,
        'video_access_request_id' => null,
        'code' => 'ZX12CV34',
        'status' => VideoAccessCodeStatus::Active,
        'generated_by' => $admin->id,
        'generated_at' => now(),
        'expires_at' => now()->addDays(30),
    ]);

    $this->mock(EvolutionApiClient::class)
        ->shouldReceive('sendText')
        ->once()
        ->andThrow(new \RuntimeException('Cannot read properties of undefined (reading \'onWhatsApp\')'));

    $this->app->make(EvolutionApiClient::class)
        ->shouldReceive('fetchInstances')
        ->once()
        ->andReturn([
            [
                'name' => 'otp-instance',
                'connectionStatus' => 'close',
                'disconnectionReasonCode' => 401,
                'disconnectionAt' => '2026-02-27T22:26:48.353Z',
            ],
        ]);

    $response = $this->postJson(
        "/api/v1/admin/centers/{$center->id}/video-access-codes/{$code->id}/send-whatsapp",
        ['format' => 'text_code'],
        $this->adminHeaders()
    );

    $response->assertStatus(500)
        ->assertJsonPath('error.code', 'WHATSAPP_SEND_FAILED')
        ->assertJsonPath('error.message', fn ($message) => is_string($message)
            && str_contains($message, 'Evolution instance "otp-instance" is not connected')
            && str_contains($message, 'Reason code: 401')
            && str_contains($message, '2026-02-27T22:26:48.353Z')
        );
});

it('returns actionable error when evolution returns truncated undefined-reading error', function (): void {
    config(['evolution.otp_instance_name' => 'otp-instance']);

    $center = Center::factory()->create();
    $admin = $this->asCenterAdmin($center);

    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
        'country_code' => '+20',
        'phone' => '1012345678',
    ]);

    $course = Course::factory()->create([
        'center_id' => $center->id,
    ]);

    $video = Video::factory()->create([
        'center_id' => $center->id,
        'created_by' => $admin->id,
    ]);

    $enrollment = Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'center_id' => $center->id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    $code = VideoAccessCode::query()->create([
        'user_id' => $student->id,
        'video_id' => $video->id,
        'course_id' => $course->id,
        'center_id' => $center->id,
        'enrollment_id' => $enrollment->id,
        'video_access_request_id' => null,
        'code' => 'TR12NC34',
        'status' => VideoAccessCodeStatus::Active,
        'generated_by' => $admin->id,
        'generated_at' => now(),
        'expires_at' => now()->addDays(30),
    ]);

    $this->mock(EvolutionApiClient::class)
        ->shouldReceive('sendMedia')
        ->once()
        ->andThrow(new \RuntimeException('TypeError: Cannot read properties of undefined (re'));

    $this->app->make(EvolutionApiClient::class)
        ->shouldReceive('fetchInstances')
        ->once()
        ->andReturn([
            [
                'name' => 'otp-instance',
                'connectionStatus' => 'close',
                'disconnectionReasonCode' => 401,
                'disconnectionAt' => '2026-02-27T22:26:48.353Z',
            ],
        ]);

    $response = $this->postJson(
        "/api/v1/admin/centers/{$center->id}/video-access-codes/{$code->id}/send-whatsapp",
        ['format' => 'qr_code'],
        $this->adminHeaders()
    );

    $response->assertStatus(500)
        ->assertJsonPath('error.code', 'WHATSAPP_SEND_FAILED')
        ->assertJsonPath('error.message', fn ($message) => is_string($message)
            && str_contains($message, 'Evolution instance "otp-instance" is not connected')
            && str_contains($message, 'Reason code: 401')
            && str_contains($message, '2026-02-27T22:26:48.353Z')
        );
});

it('sends qr code via whatsapp media using raw base64 payload', function (): void {
    config(['evolution.otp_instance_name' => 'otp-instance']);

    $center = Center::factory()->create();
    $admin = $this->asCenterAdmin($center);

    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
        'country_code' => '+20',
        'phone' => '1012345678',
    ]);

    $course = Course::factory()->create([
        'center_id' => $center->id,
    ]);

    $video = Video::factory()->create([
        'center_id' => $center->id,
        'created_by' => $admin->id,
    ]);

    $enrollment = Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'center_id' => $center->id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    $code = VideoAccessCode::query()->create([
        'user_id' => $student->id,
        'video_id' => $video->id,
        'course_id' => $course->id,
        'center_id' => $center->id,
        'enrollment_id' => $enrollment->id,
        'video_access_request_id' => null,
        'code' => 'QR12CD34',
        'status' => VideoAccessCodeStatus::Active,
        'generated_by' => $admin->id,
        'generated_at' => now(),
        'expires_at' => now()->addDays(30),
    ]);

    $this->mock(EvolutionApiClient::class)
        ->shouldReceive('sendMedia')
        ->once()
        ->with('otp-instance', \Mockery::on(function (array $payload) use ($code): bool {
            $media = (string) ($payload['media'] ?? '');

            return ($payload['number'] ?? null) === '201012345678'
                && ($payload['mediatype'] ?? null) === 'image'
                && ! str_starts_with($media, 'data:image/')
                && base64_decode($media, true) !== false
                && is_string($payload['caption'] ?? null)
                && str_contains((string) $payload['caption'], $code->code);
        }))
        ->andReturn(['key' => ['id' => 'msg-qr-1']]);

    $response = $this->postJson(
        "/api/v1/admin/centers/{$center->id}/video-access-codes/{$code->id}/send-whatsapp",
        ['format' => 'qr_code'],
        $this->adminHeaders()
    );

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Code sent to student via WhatsApp');
});
