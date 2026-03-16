<?php

declare(strict_types=1);

namespace App\Services\VideoAccess\Contracts;

use App\Models\User;
use App\Models\VideoAccess;
use App\Models\VideoCodeBatch;
use App\Models\VideoCodeRedemption;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface VideoCodeRedemptionServiceInterface
{
    /**
     * Redeem a video access code.
     *
     * @return array{
     *   video_access: VideoAccess,
     *   redemption: VideoCodeRedemption,
     *   batch: VideoCodeBatch,
     *   sequence: int,
     *   redeemed_at: \Illuminate\Support\Carbon
     * }
     *
     * @throws \App\Exceptions\DomainException If code is invalid or cannot be redeemed
     */
    public function redeemCode(User $student, string $code): array;

    /**
     * Validate a code without redeeming it.
     * Always returns 200 with valid=true/false, never throws for validation failures.
     *
     * @return array{
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
    public function validateCode(User $student, string $code): array;

    /**
     * Get all codes redeemed by a student.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, VideoCodeRedemption>
     */
    public function getStudentRedemptions(User $student): \Illuminate\Database\Eloquent\Collection;

    /**
     * Get paginated redeemed codes for a student.
     *
     * @return LengthAwarePaginator<VideoCodeRedemption>
     */
    public function getStudentRedemptionsPaginated(
        User $student,
        int $perPage = 15,
        int $page = 1,
        ?int $courseId = null
    ): LengthAwarePaginator;

    /**
     * Get total view limit for a user and course video based on redeemed codes.
     */
    public function getTotalViewLimit(User $user, int $videoId, int $courseId): int;
}
