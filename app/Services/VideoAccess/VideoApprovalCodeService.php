<?php

declare(strict_types=1);

namespace App\Services\VideoAccess;

use App\Enums\VideoAccessCodeStatus;
use App\Enums\WhatsAppCodeFormat;
use App\Exceptions\DomainException;
use App\Models\Center;
use App\Models\CenterSetting;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoAccess;
use App\Models\VideoAccessCode;
use App\Models\VideoAccessRequest;
use App\Services\Access\CourseAccessService;
use App\Services\Access\EnrollmentAccessService;
use App\Services\Access\StudentAccessService;
use App\Services\Centers\CenterScopeService;
use App\Services\Evolution\EvolutionApiClient;
use App\Services\VideoAccess\Contracts\VideoApprovalCodeServiceInterface;
use App\Services\VideoAccess\Contracts\VideoApprovalServiceInterface;
use App\Support\ErrorCodes;
use BaconQrCode\Renderer\GDLibRenderer;
use BaconQrCode\Writer;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class VideoApprovalCodeService implements VideoApprovalCodeServiceInterface
{
    public function __construct(
        private readonly StudentAccessService $studentAccessService,
        private readonly CenterScopeService $centerScopeService,
        private readonly CourseAccessService $courseAccessService,
        private readonly EnrollmentAccessService $enrollmentAccessService,
        private readonly VideoApprovalServiceInterface $videoApprovalService,
        private readonly EvolutionApiClient $evolutionApiClient
    ) {}

    public function generate(
        User $admin,
        User $student,
        Video $video,
        Course $course,
        Enrollment $enrollment,
        ?VideoAccessRequest $request = null
    ): VideoAccessCode {
        if ($admin->is_student) {
            $this->deny(ErrorCodes::UNAUTHORIZED, 'Only admins can generate access codes.', 403);
        }

        $this->centerScopeService->assertAdminSameCenter($admin, $course);
        $this->studentAccessService->assertStudent(
            $student,
            'Only students can receive video access codes.',
            ErrorCodes::NOT_STUDENT,
            422
        );

        $this->courseAccessService->assertVideoInCourse($course, $video);

        return DB::transaction(function () use ($admin, $student, $video, $course, $enrollment, $request): VideoAccessCode {
            /** @var Enrollment|null $lockedEnrollment */
            $lockedEnrollment = Enrollment::query()
                ->whereKey($enrollment->id)
                ->lockForUpdate()
                ->first();

            if (
                ! $lockedEnrollment instanceof Enrollment
                || (int) $lockedEnrollment->user_id !== (int) $student->id
                || (int) $lockedEnrollment->course_id !== (int) $course->id
            ) {
                $this->deny(ErrorCodes::ENROLLMENT_REQUIRED, 'Active enrollment required.', 403);
            }

            $this->enrollmentAccessService->assertActiveEnrollment($student, $course);

            if ($this->videoApprovalService->hasAccess($student, $video, $course)) {
                $this->deny(ErrorCodes::VIDEO_ACCESS_ALREADY_GRANTED, 'Video access already granted.', 422);
            }

            $this->assertNoExistingValidCode($student, $video, $course, true);

            $codeValue = $this->generateUniqueCode();

            /** @var VideoAccessCode $code */
            $code = VideoAccessCode::query()->create([
                'user_id' => $student->id,
                'video_id' => $video->id,
                'course_id' => $course->id,
                'center_id' => $course->center_id,
                'enrollment_id' => $lockedEnrollment->id,
                'video_access_request_id' => $request?->id,
                'code' => $codeValue,
                'status' => VideoAccessCodeStatus::Active,
                'generated_by' => $admin->id,
                'generated_at' => Carbon::now(),
                'expires_at' => $this->calculateExpiry($course->center),
            ]);

            return $code->fresh(['user', 'video', 'course', 'request']) ?? $code;
        });
    }

    public function redeem(User $student, string $code): VideoAccess
    {
        $this->studentAccessService->assertStudent(
            $student,
            'Only students can redeem video access codes.',
            ErrorCodes::UNAUTHORIZED,
            403
        );

        $normalized = strtoupper(trim($code));

        /** @var VideoAccessCode|null $resolvedCode */
        $resolvedCode = VideoAccessCode::query()
            ->where('code', $normalized)
            ->first();

        if (! $resolvedCode instanceof VideoAccessCode) {
            $this->deny(ErrorCodes::VIDEO_CODE_INVALID, 'Code not found.', 404);
        }

        return DB::transaction(function () use ($student, $resolvedCode): VideoAccess {
            /** @var VideoAccessCode|null $lockedCode */
            $lockedCode = VideoAccessCode::query()
                ->whereKey($resolvedCode->id)
                ->lockForUpdate()
                ->first();

            if (! $lockedCode instanceof VideoAccessCode) {
                $this->deny(ErrorCodes::VIDEO_CODE_INVALID, 'Code not found.', 404);
            }

            $this->assertCodeRedeemable($student, $lockedCode);

            /** @var Course $course */
            $course = $lockedCode->course;

            $this->enrollmentAccessService->assertActiveEnrollment($student, $course);

            $access = $this->videoApprovalService->grantFromCode($student, $lockedCode);

            $lockedCode->status = VideoAccessCodeStatus::Used;
            $lockedCode->used_at = Carbon::now();
            $lockedCode->save();

            return $access->fresh(['user', 'video', 'course', 'code']) ?? $access;
        });
    }

    public function validate(string $code): ?VideoAccessCode
    {
        $normalized = strtoupper(trim($code));

        /** @var VideoAccessCode|null $resolved */
        $resolved = VideoAccessCode::query()
            ->where('code', $normalized)
            ->first();

        if (! $resolved instanceof VideoAccessCode) {
            return null;
        }

        if ($resolved->status !== VideoAccessCodeStatus::Active) {
            return null;
        }

        if ($this->isExpired($resolved)) {
            $this->markExpired($resolved);

            return null;
        }

        return $resolved;
    }

    public function regenerate(User $admin, VideoAccessCode $code): VideoAccessCode
    {
        if ($admin->is_student) {
            $this->deny(ErrorCodes::UNAUTHORIZED, 'Only admins can regenerate access codes.', 403);
        }

        $this->centerScopeService->assertAdminSameCenter($admin, $code);

        return DB::transaction(function () use ($admin, $code): VideoAccessCode {
            $freshCode = $code->fresh() ?? $code;
            $this->revoke($admin, $freshCode);

            /** @var User $student */
            $student = $freshCode->user;
            /** @var Video $video */
            $video = $freshCode->video;
            /** @var Course $course */
            $course = $freshCode->course;
            /** @var Enrollment $enrollment */
            $enrollment = $freshCode->enrollment;

            return $this->generate(
                admin: $admin,
                student: $student,
                video: $video,
                course: $course,
                enrollment: $enrollment,
                request: $freshCode->request
            );
        });
    }

    public function revoke(User $admin, VideoAccessCode $code): VideoAccessCode
    {
        if ($admin->is_student) {
            $this->deny(ErrorCodes::UNAUTHORIZED, 'Only admins can revoke access codes.', 403);
        }

        $this->centerScopeService->assertAdminSameCenter($admin, $code);

        if ($code->status === VideoAccessCodeStatus::Revoked || $code->status === VideoAccessCodeStatus::Used) {
            return $code;
        }

        if ($this->isExpired($code)) {
            $this->markExpired($code);

            return $code->fresh() ?? $code;
        }

        $code->status = VideoAccessCodeStatus::Revoked;
        $code->revoked_at = Carbon::now();
        $code->revoked_by = $admin->id;
        $code->save();

        return $code->fresh() ?? $code;
    }

    public function getQrCodeDataUrl(VideoAccessCode $code): string
    {
        $writer = new Writer(new GDLibRenderer(size: 300, margin: 2, imageFormat: 'png', compressionQuality: 9));
        $png = $writer->writeString($code->code);

        return 'data:image/png;base64,'.base64_encode($png);
    }

    public function sendViaWhatsApp(VideoAccessCode $code, WhatsAppCodeFormat $format): void
    {
        /** @var User $student */
        $student = $code->user;

        $phone = trim((string) $student->phone);
        if ($phone === '') {
            $this->deny(ErrorCodes::STUDENT_NO_PHONE, 'Student has no phone number.', 422);
        }

        $countryCode = trim((string) ($student->country_code ?? ''));
        $destination = $countryCode !== '' ? $countryCode.$phone : $phone;
        $normalizedDestination = $this->normalizeDestination($destination);

        $instanceName = (string) config('evolution.otp_instance_name', '');

        if ($instanceName === '') {
            $this->deny(ErrorCodes::WHATSAPP_SEND_FAILED, 'WhatsApp instance is not configured.', 500);
        }

        /** @var Video $video */
        $video = $code->video;

        try {
            if ($format === WhatsAppCodeFormat::QrCode) {
                $this->evolutionApiClient->sendMedia($instanceName, [
                    'number' => $normalizedDestination,
                    'mediatype' => 'image',
                    'media' => $this->getQrCodeDataUrl($code),
                    'caption' => sprintf(
                        "Your access code for '%s': %s",
                        $video->translate('title') ?: 'Video',
                        $code->code
                    ),
                    'fileName' => sprintf('video-access-%d.png', $code->id),
                ]);

                return;
            }

            $this->evolutionApiClient->sendText($instanceName, [
                'number' => $normalizedDestination,
                'text' => sprintf(
                    "Your access code for '%s' is: %s\n\nEnter this code in the app to unlock the video.",
                    $video->translate('title') ?: 'Video',
                    $code->code
                ),
            ]);
        } catch (\Throwable $throwable) {
            $this->deny(
                ErrorCodes::WHATSAPP_SEND_FAILED,
                'Failed to send WhatsApp message: '.$this->resolveWhatsAppSendFailureMessage($throwable, $instanceName),
                500
            );
        }
    }

    public function bulkSendViaWhatsApp(array $codeIds, WhatsAppCodeFormat $format): array
    {
        $uniqueIds = array_values(array_unique(array_map('intval', $codeIds)));

        $codes = VideoAccessCode::query()
            ->whereIn('id', $uniqueIds)
            ->with(['user', 'video'])
            ->get()
            ->keyBy('id');

        $sent = 0;
        $failed = 0;
        $results = [];

        foreach ($uniqueIds as $codeId) {
            /** @var VideoAccessCode|null $resolved */
            $resolved = $codes->get($codeId);

            if (! $resolved instanceof VideoAccessCode) {
                $failed++;
                $results[] = [
                    'code_id' => $codeId,
                    'success' => false,
                    'error' => 'Code not found.',
                ];

                continue;
            }

            try {
                $this->sendViaWhatsApp($resolved, $format);
                $sent++;
                $results[] = [
                    'code_id' => $codeId,
                    'success' => true,
                ];
            } catch (\Throwable $throwable) {
                $failed++;
                $results[] = [
                    'code_id' => $codeId,
                    'success' => false,
                    'error' => $throwable->getMessage(),
                ];
            }
        }

        return [
            'sent' => $sent,
            'failed' => $failed,
            'results' => $results,
        ];
    }

    private function generateUniqueCode(int $length = 8): string
    {
        $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $maxIndex = strlen($characters) - 1;

        for ($attempt = 0; $attempt < 20; $attempt++) {
            $code = '';

            for ($i = 0; $i < $length; $i++) {
                $code .= $characters[random_int(0, $maxIndex)];
            }

            $exists = VideoAccessCode::query()->where('code', $code)->exists();
            if (! $exists) {
                return $code;
            }
        }

        throw new \RuntimeException('Unable to generate a unique access code.');
    }

    private function calculateExpiry(?Center $center): ?Carbon
    {
        if (! $center instanceof Center) {
            return null;
        }

        /** @var CenterSetting|null $setting */
        $setting = $center->setting()->first();
        $settings = $setting instanceof CenterSetting && is_array($setting->settings) ? $setting->settings : [];
        $days = $settings['video_code_expiry_days'] ?? null;

        if (! is_numeric($days) || (int) $days <= 0) {
            return null;
        }

        return Carbon::now()->addDays((int) $days);
    }

    private function assertCodeRedeemable(User $student, VideoAccessCode $code): void
    {
        if ((int) $code->user_id !== (int) $student->id) {
            $this->deny(ErrorCodes::VIDEO_CODE_WRONG_USER, 'Code belongs to another student.', 403);
        }

        if ($code->status === VideoAccessCodeStatus::Used) {
            $this->deny(ErrorCodes::VIDEO_CODE_USED, 'Code already used.', 410);
        }

        if ($code->status === VideoAccessCodeStatus::Revoked) {
            $this->deny(ErrorCodes::VIDEO_CODE_REVOKED, 'Code was revoked.', 410);
        }

        if ($code->status === VideoAccessCodeStatus::Expired) {
            $this->deny(ErrorCodes::VIDEO_CODE_EXPIRED, 'Code has expired.', 410);
        }

        if ($code->status !== VideoAccessCodeStatus::Active) {
            $this->deny(ErrorCodes::VIDEO_CODE_INVALID, 'Code is invalid.', 404);
        }

        if ($this->isExpired($code)) {
            $this->markExpired($code);
            $this->deny(ErrorCodes::VIDEO_CODE_EXPIRED, 'Code has expired.', 410);
        }
    }

    private function isExpired(VideoAccessCode $code): bool
    {
        return $code->expires_at !== null && $code->expires_at->isPast();
    }

    private function assertNoExistingValidCode(
        User $student,
        Video $video,
        Course $course,
        bool $lockForUpdate = false
    ): void {
        $query = VideoAccessCode::query()
            ->where('user_id', $student->id)
            ->where('video_id', $video->id)
            ->where('course_id', $course->id)
            ->where('status', VideoAccessCodeStatus::Active->value);

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        /** @var \Illuminate\Database\Eloquent\Collection<int, VideoAccessCode> $activeCodes */
        $activeCodes = $query->orderByDesc('id')->get();

        foreach ($activeCodes as $activeCode) {
            if ($this->isExpired($activeCode)) {
                $this->markExpired($activeCode);

                continue;
            }

            $expiryReason = $activeCode->expires_at instanceof Carbon
                ? ' Existing code expires at '.$activeCode->expires_at->toISOString().'.'
                : ' Existing code does not expire.';

            $this->deny(
                ErrorCodes::VIDEO_CODE_ACTIVE_EXISTS,
                'Student already has a valid video access code for this video.'.$expiryReason,
                422
            );
        }
    }

    private function markExpired(VideoAccessCode $code): void
    {
        if ($code->status !== VideoAccessCodeStatus::Active) {
            return;
        }

        $code->status = VideoAccessCodeStatus::Expired;
        $code->save();
    }

    private function normalizeDestination(string $destination): string
    {
        $normalized = preg_replace('/\D+/', '', $destination) ?? '';

        if (str_starts_with($normalized, '00')) {
            $normalized = substr($normalized, 2);
        }

        if ($normalized === '') {
            throw new \RuntimeException('Destination phone must contain digits.');
        }

        return $normalized;
    }

    private function resolveWhatsAppSendFailureMessage(\Throwable $throwable, string $instanceName): string
    {
        $message = $throwable->getMessage();
        if (! $this->isLikelyInstanceConnectionError($message)) {
            return $message;
        }

        $instanceStateMessage = $this->resolveInstanceStateErrorMessage($instanceName);
        if ($instanceStateMessage !== null) {
            return $instanceStateMessage;
        }

        return $message;
    }

    private function isLikelyInstanceConnectionError(string $message): bool
    {
        $normalized = strtolower($message);

        return str_contains($normalized, 'onwha')
            || str_contains($normalized, 'log out instance')
            || str_contains($normalized, 'unauthorized');
    }

    private function resolveInstanceStateErrorMessage(string $instanceName): ?string
    {
        try {
            $instances = $this->evolutionApiClient->fetchInstances();
        } catch (\Throwable) {
            return null;
        }

        foreach ($instances as $instance) {
            if (! is_array($instance)) {
                continue;
            }

            if ((string) ($instance['name'] ?? '') !== $instanceName) {
                continue;
            }

            $status = (string) ($instance['connectionStatus'] ?? 'unknown');
            if (in_array(strtolower($status), ['open', 'connected', 'online'], true)) {
                return null;
            }

            $reasonCode = $instance['disconnectionReasonCode'] ?? null;
            $disconnectedAt = (string) ($instance['disconnectionAt'] ?? '');

            $reasonPart = is_scalar($reasonCode) ? ' Reason code: '.$reasonCode.'.' : '';
            $disconnectedAtPart = $disconnectedAt !== '' ? ' Disconnected at '.$disconnectedAt.'.' : '';

            return sprintf(
                'Evolution instance "%s" is not connected (status: %s). Reconnect the instance in Evolution Manager and retry.%s%s',
                $instanceName,
                $status !== '' ? $status : 'unknown',
                $reasonPart,
                $disconnectedAtPart
            );
        }

        return sprintf(
            'Evolution instance "%s" was not found. Create/connect the instance and retry.',
            $instanceName
        );
    }

    /**
     * @return never
     */
    private function deny(string $code, string $message, int $status): void
    {
        throw new DomainException($message, $code, $status);
    }
}
