<?php

declare(strict_types=1);

use App\Enums\BulkItemStatus;
use App\Enums\BulkJobStatus;
use App\Enums\WhatsAppCodeFormat;
use App\Jobs\ProcessBulkWhatsAppJob;
use App\Jobs\SendSingleWhatsAppCodeJob;
use App\Models\BulkWhatsAppJob;
use App\Models\BulkWhatsAppJobItem;
use App\Models\Center;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoAccessCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Helpers\AdminTestHelper;

uses(RefreshDatabase::class, AdminTestHelper::class)->group('video-access', 'admin', 'bulk-whatsapp');

it('reclaims stale processing items and re-dispatches them', function (): void {
    $center = Center::factory()->create();
    $admin = $this->asCenterAdmin($center);

    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);

    $course = Course::factory()->create([
        'center_id' => $center->id,
    ]);

    $video = Video::factory()->create([
        'center_id' => $center->id,
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
        'code' => 'STALE001',
        'status' => \App\Enums\VideoAccessCodeStatus::Active,
        'generated_by' => $admin->id,
        'generated_at' => now(),
    ]);

    $job = BulkWhatsAppJob::query()->create([
        'center_id' => $center->id,
        'total_codes' => 1,
        'sent_count' => 0,
        'failed_count' => 0,
        'status' => BulkJobStatus::Processing,
        'format' => WhatsAppCodeFormat::TextCode,
        'created_by' => $admin->id,
        'settings' => [
            'delay_seconds' => 0,
            'batch_size' => 50,
            'batch_pause_seconds' => 0,
            'max_retries' => 2,
            'max_failures_before_pause' => 10,
            'processing_timeout_seconds' => 30,
        ],
        'started_at' => now()->subMinute(),
    ]);

    $item = BulkWhatsAppJobItem::query()->create([
        'bulk_job_id' => $job->id,
        'video_access_code_id' => $code->id,
        'status' => BulkItemStatus::Processing,
        'attempts' => 0,
    ]);
    $item->forceFill(['updated_at' => now()->subMinutes(10)])->save();

    Queue::fake();

    (new ProcessBulkWhatsAppJob($job->id))->handle();

    $item->refresh();
    expect($item->status)->toBe(BulkItemStatus::Pending);

    Queue::assertPushed(SendSingleWhatsAppCodeJob::class, static function (SendSingleWhatsAppCodeJob $queued) use ($item): bool {
        return $queued->itemId === $item->id;
    });
});
