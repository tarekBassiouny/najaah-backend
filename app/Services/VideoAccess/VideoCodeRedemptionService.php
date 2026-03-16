<?php

declare(strict_types=1);

namespace App\Services\VideoAccess;

use App\Enums\CenterType;
use App\Exceptions\DomainException;
use App\Models\Course;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoAccess;
use App\Models\VideoCodeBatch;
use App\Models\VideoCodeRedemption;
use App\Services\Access\StudentAccessService;
use App\Services\VideoAccess\Contracts\VideoCodeRedemptionServiceInterface;
use App\Support\ErrorCodes;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * @phpstan-type ValidationResult array{
 *   valid: bool,
 *   batch: VideoCodeBatch|null,
 *   sequence: int|null,
 *   video_id: int|null,
 *   video_title: string|null,
 *   course_id: int|null,
 *   course_title: string|null,
 *   center_id: int|null,
 *   view_limit: int|null,
 *   can_redeem: bool,
 *   reason: string|null
 * }
 */
class VideoCodeRedemptionService implements VideoCodeRedemptionServiceInterface
{
    public function __construct(
        private readonly VideoCodeGenerator $codeGenerator,
        private readonly StudentAccessService $studentAccessService
    ) {}

    public function redeemCode(User $student, string $code): array
    {
        $this->studentAccessService->assertStudent(
            $student,
            'Only students can redeem video access codes.',
            ErrorCodes::NOT_STUDENT,
            403
        );

        $parsed = $this->codeGenerator->parseAndValidate($code);

        if ($parsed === null) {
            $this->deny(ErrorCodes::VIDEO_CODE_INVALID, 'Invalid video access code.', 404);
        }

        /** @var VideoCodeBatch $batch */
        $batch = $parsed['batch'];
        $sequence = $parsed['sequence'];

        // Verify center eligibility - student must belong to the batch's center
        $this->assertStudentCenterEligibility($student, $batch);

        return DB::transaction(function () use ($student, $batch, $sequence): array {
            // Lock the batch for update
            /** @var VideoCodeBatch|null $lockedBatch */
            $lockedBatch = VideoCodeBatch::query()
                ->whereKey($batch->id)
                ->lockForUpdate()
                ->first();

            if (! $lockedBatch instanceof VideoCodeBatch) {
                $this->deny(ErrorCodes::VIDEO_CODE_INVALID, 'Invalid video access code.', 404);
            }

            // Check if batch allows redemption
            if (! $lockedBatch->canRedeem()) {
                $this->denyFromBatchAvailability($lockedBatch);
            }

            /** @var VideoCodeRedemption|null $redemption */
            $redemption = VideoCodeRedemption::query()
                ->where('batch_id', $lockedBatch->id)
                ->where('sequence_number', $sequence)
                ->lockForUpdate()
                ->first();

            if ($redemption instanceof VideoCodeRedemption) {
                $this->denyAlreadyRedeemed($redemption, $student);
            }

            /** @var Video $video */
            $video = $lockedBatch->video;
            /** @var Course $course */
            $course = $lockedBatch->course;

            // Create or update VideoAccess
            $videoAccess = $this->grantOrStackAccess(
                $student,
                $video,
                $course,
                $lockedBatch->view_limit_per_code
            );

            $redeemedAt = Carbon::now();

            // Create redemption record
            /** @var VideoCodeRedemption $redemption */
            $redemption = VideoCodeRedemption::query()->create([
                'batch_id' => $lockedBatch->id,
                'sequence_number' => $sequence,
                'user_id' => $student->id,
                'video_access_id' => $videoAccess->id,
                'redeemed_at' => $redeemedAt,
            ]);

            // Increment redeemed count atomically
            VideoCodeBatch::query()
                ->whereKey($lockedBatch->id)
                ->increment('redeemed_count');

            $videoAccess = $videoAccess->fresh(['user', 'video', 'course']) ?? $videoAccess;

            return [
                'video_access' => $videoAccess,
                'redemption' => $redemption,
                'batch' => $lockedBatch,
                'sequence' => $sequence,
                'redeemed_at' => $redeemedAt,
            ];
        });
    }

    /**
     * @return ValidationResult
     */
    public function validateCode(User $student, string $code): array
    {
        $this->studentAccessService->assertStudent(
            $student,
            'Only students can validate video access codes.',
            ErrorCodes::NOT_STUDENT,
            403
        );

        $parsed = $this->codeGenerator->parseAndValidate($code);

        if ($parsed === null) {
            return [
                'valid' => false,
                'batch' => null,
                'sequence' => null,
                'video_id' => null,
                'video_title' => null,
                'course_id' => null,
                'course_title' => null,
                'center_id' => null,
                'view_limit' => null,
                'can_redeem' => false,
                'reason' => 'Invalid code or code not found.',
            ];
        }

        /** @var VideoCodeBatch $batch */
        $batch = $parsed['batch'];
        $sequence = $parsed['sequence'];

        $batch->loadMissing(['video', 'course']);

        // Check center eligibility
        if (! $this->isStudentCenterEligible($student, $batch)) {
            return $this->invalidValidationResult(
                batch: $batch,
                sequence: $sequence,
                reason: 'This code is not valid for your center.'
            );
        }

        /** @var VideoCodeRedemption|null $redemption */
        $redemption = VideoCodeRedemption::query()
            ->where('batch_id', $batch->id)
            ->where('sequence_number', $sequence)
            ->first();

        if ($redemption instanceof VideoCodeRedemption) {
            return $this->invalidValidationResult(
                batch: $batch,
                sequence: $sequence,
                reason: $redemption->user_id === $student->id
                    ? 'You have already redeemed this code.'
                    : 'This code has already been redeemed.'
            );
        }

        if (! $batch->canRedeem()) {
            return $this->invalidValidationResultFromBatchAvailability($batch, $sequence);
        }

        return [
            'valid' => true,
            'batch' => $batch,
            'sequence' => $sequence,
            'video_id' => $batch->video_id,
            'video_title' => $batch->video->translate('title'),
            'course_id' => $batch->course_id,
            'course_title' => $batch->course->translate('title'),
            'center_id' => $batch->center_id,
            'view_limit' => $batch->view_limit_per_code,
            'can_redeem' => true,
            'reason' => null,
        ];
    }

    public function getStudentRedemptions(User $student): Collection
    {
        return VideoCodeRedemption::query()
            ->where('user_id', $student->id)
            ->with(['batch.video', 'batch.course', 'videoAccess'])
            ->orderByDesc('redeemed_at')
            ->get();
    }

    public function getStudentRedemptionsPaginated(
        User $student,
        int $perPage = 15,
        int $page = 1,
        ?int $courseId = null
    ): LengthAwarePaginator {
        $query = VideoCodeRedemption::query()
            ->where('user_id', $student->id)
            ->with(['batch.video', 'batch.course', 'videoAccess'])
            ->orderByDesc('redeemed_at');

        if ($courseId !== null) {
            $query->whereHas('batch', function ($batchQuery) use ($courseId): void {
                $batchQuery->where('course_id', $courseId);
            });
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    public function getTotalViewLimit(User $user, int $videoId, int $courseId): int
    {
        // Sum view limits from all batches where the user has redeemed codes for this course video
        $total = VideoCodeRedemption::query()
            ->where('user_id', $user->id)
            ->whereHas('batch', function ($query) use ($videoId, $courseId): void {
                $query->where('video_id', $videoId)
                    ->where('course_id', $courseId)
                    ->whereNull('deleted_at');
            })
            ->join('video_code_batches', 'video_code_redemptions.batch_id', '=', 'video_code_batches.id')
            ->where('video_code_batches.course_id', $courseId)
            ->sum('video_code_batches.view_limit_per_code');

        return (int) $total;
    }

    /**
     * Grant video access or stack view limit if access already exists.
     */
    private function grantOrStackAccess(
        User $student,
        Video $video,
        Course $course,
        int $viewLimit
    ): VideoAccess {
        // Check for existing active access
        /** @var VideoAccess|null $existingAccess */
        $existingAccess = VideoAccess::query()
            ->where('user_id', $student->id)
            ->where('video_id', $video->id)
            ->where('course_id', $course->id)
            ->active()
            ->first();

        if ($existingAccess instanceof VideoAccess) {
            // Stack the view limit
            $currentLimit = $existingAccess->total_view_limit ?? 0;
            $existingAccess->total_view_limit = $currentLimit + $viewLimit;
            $existingAccess->save();

            return $existingAccess;
        }

        // Create new access record
        /** @var VideoAccess $access */
        $access = VideoAccess::query()->create([
            'user_id' => $student->id,
            'video_id' => $video->id,
            'course_id' => $course->id,
            'center_id' => $course->center_id,
            'enrollment_id' => null,
            'video_access_code_id' => null,
            'total_view_limit' => $viewLimit,
            'granted_at' => Carbon::now(),
        ]);

        return $access;
    }

    /**
     * @return ValidationResult
     */
    private function invalidValidationResult(
        VideoCodeBatch $batch,
        int $sequence,
        string $reason
    ): array {
        return [
            'valid' => false,
            'batch' => $batch,
            'sequence' => $sequence,
            'video_id' => $batch->video_id,
            'video_title' => $batch->video->translate('title'),
            'course_id' => $batch->course_id,
            'course_title' => $batch->course->translate('title'),
            'center_id' => $batch->center_id,
            'view_limit' => $batch->view_limit_per_code,
            'can_redeem' => false,
            'reason' => $reason,
        ];
    }

    /**
     * @return ValidationResult
     */
    private function invalidValidationResultFromBatchAvailability(VideoCodeBatch $batch, int $sequence): array
    {
        if ($batch->isClosed() && $batch->sold_limit === null) {
            return $this->invalidValidationResult(
                batch: $batch,
                sequence: $sequence,
                reason: 'This code batch is no longer active.'
            );
        }

        return $this->invalidValidationResult(
            batch: $batch,
            sequence: $sequence,
            reason: 'Code redemption limit reached.'
        );
    }

    /**
     * Check if student belongs to the batch's center (for branded centers)
     * or if the batch is for an unbranded center (accessible to all).
     */
    private function isStudentCenterEligible(User $student, VideoCodeBatch $batch): bool
    {
        // If student has no center (Najaah app user), they can only access unbranded center content
        if ($student->center_id === null) {
            // Load center to check if it's unbranded
            $batch->loadMissing('center');

            return $batch->center->type === CenterType::Unbranded;
        }

        // Student has a center - must match batch's center
        return (int) $student->center_id === (int) $batch->center_id;
    }

    /**
     * Assert student center eligibility, throw if not eligible.
     */
    private function assertStudentCenterEligibility(User $student, VideoCodeBatch $batch): void
    {
        if (! $this->isStudentCenterEligible($student, $batch)) {
            $this->deny(
                ErrorCodes::CENTER_MISMATCH,
                'This code is not valid for your center.',
                403
            );
        }
    }

    /**
     * @return never
     */
    private function denyAlreadyRedeemed(VideoCodeRedemption $redemption, User $student): void
    {
        if ($redemption->user_id === $student->id) {
            $this->deny(
                ErrorCodes::VIDEO_CODE_ALREADY_REDEEMED_BY_YOU,
                'You have already redeemed this code.',
                409
            );
        }

        $this->deny(
            ErrorCodes::VIDEO_CODE_ALREADY_REDEEMED,
            'This code has already been redeemed.',
            409
        );
    }

    /**
     * @return never
     */
    private function denyFromBatchAvailability(VideoCodeBatch $batch): void
    {
        if ($batch->isClosed() && $batch->sold_limit === null) {
            $this->deny(
                ErrorCodes::VIDEO_CODE_BATCH_CLOSED,
                'This code batch is no longer active.',
                403
            );
        }

        $this->deny(
            ErrorCodes::VIDEO_CODE_BATCH_LIMIT_REACHED,
            'Code redemption limit reached.',
            403
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
