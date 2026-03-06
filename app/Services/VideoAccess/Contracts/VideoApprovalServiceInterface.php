<?php

declare(strict_types=1);

namespace App\Services\VideoAccess\Contracts;

use App\Models\Center;
use App\Models\Course;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoAccess;
use App\Models\VideoAccessCode;

interface VideoApprovalServiceInterface
{
    public function requiresApproval(Center $center, ?Course $course = null): bool;

    public function hasAccess(User $student, Video $video, Course $course): bool;

    public function assertApprovalAccess(User $student, Center $center, Course $course, Video $video): void;

    public function grantFromCode(User $student, VideoAccessCode $code): VideoAccess;

    public function revoke(User $admin, VideoAccess $access): VideoAccess;
}
