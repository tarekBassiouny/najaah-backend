<?php

declare(strict_types=1);

namespace App\Services\VideoAccess\Contracts;

use App\Enums\WhatsAppCodeFormat;
use App\Models\Center;
use App\Models\Course;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoAccessRequest;
use App\Services\VideoAccess\Data\ApprovalResult;

interface VideoApprovalRequestServiceInterface
{
    public function createForStudent(User $student, Center $center, Course $course, Video $video, ?string $reason = null): VideoAccessRequest;

    public function approve(
        User $admin,
        VideoAccessRequest $request,
        ?string $decisionReason = null,
        bool $sendWhatsApp = false,
        ?WhatsAppCodeFormat $format = null
    ): ApprovalResult;

    public function reject(User $admin, VideoAccessRequest $request, ?string $decisionReason = null): VideoAccessRequest;

    /**
     * @param  array<int, int|string>  $requestIds
     * @return array<string, mixed>
     */
    public function bulkApprove(
        User $admin,
        array $requestIds,
        ?string $decisionReason = null,
        bool $sendWhatsApp = false,
        ?WhatsAppCodeFormat $format = null,
        ?int $forcedCenterId = null
    ): array;

    /**
     * @param  array<int, int|string>  $requestIds
     * @return array<string, mixed>
     */
    public function bulkReject(User $admin, array $requestIds, ?string $decisionReason = null, ?int $forcedCenterId = null): array;

    /**
     * @return array{has_access:bool,status:?string,pending_request_id:?int,can_request:bool}
     */
    public function statusForStudent(User $student, Course $course, Video $video): array;
}
