<?php

declare(strict_types=1);

namespace App\Services\VideoAccess\Contracts;

use App\Enums\WhatsAppCodeFormat;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoAccess;
use App\Models\VideoAccessCode;
use App\Models\VideoAccessRequest;

interface VideoApprovalCodeServiceInterface
{
    public function generate(
        User $admin,
        User $student,
        Video $video,
        Course $course,
        Enrollment $enrollment,
        ?VideoAccessRequest $request = null
    ): VideoAccessCode;

    public function redeem(User $student, string $code): VideoAccess;

    public function validate(string $code): ?VideoAccessCode;

    public function regenerate(User $admin, VideoAccessCode $code): VideoAccessCode;

    public function revoke(User $admin, VideoAccessCode $code): VideoAccessCode;

    public function getQrCodeDataUrl(VideoAccessCode $code): string;

    public function sendViaWhatsApp(VideoAccessCode $code, WhatsAppCodeFormat $format): void;

    /**
     * @param  array<int, int|string>  $codeIds
     * @return array{sent:int,failed:int,results:array<int,array<string,mixed>>}
     */
    public function bulkSendViaWhatsApp(array $codeIds, WhatsAppCodeFormat $format): array;
}
