<?php

declare(strict_types=1);

namespace App\Services\VideoAccess;

use App\Exceptions\DomainException;
use App\Models\Center;
use App\Models\CenterSetting;
use App\Models\Course;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoAccess;
use App\Models\VideoAccessCode;
use App\Services\Centers\CenterScopeService;
use App\Services\VideoAccess\Contracts\VideoApprovalServiceInterface;
use App\Support\ErrorCodes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class VideoApprovalService implements VideoApprovalServiceInterface
{
    public function __construct(
        private readonly CenterScopeService $centerScopeService
    ) {}

    public function requiresApproval(Center $center, ?Course $course = null): bool
    {
        if ($course !== null && $course->requires_video_approval !== null) {
            return (bool) $course->requires_video_approval;
        }

        /** @var CenterSetting|null $setting */
        $setting = $center->setting()->first();
        $settings = $setting instanceof CenterSetting && is_array($setting->settings) ? $setting->settings : [];

        return (bool) ($settings['requires_video_approval'] ?? false);
    }

    public function hasAccess(User $student, Video $video, Course $course): bool
    {
        return VideoAccess::query()
            ->forUserAndVideo($student->id, $video->id)
            ->where('course_id', $course->id)
            ->active()
            ->exists();
    }

    public function assertApprovalAccess(User $student, Center $center, Course $course, Video $video): void
    {
        if (! $this->requiresApproval($center, $course)) {
            return;
        }

        if (! $this->hasAccess($student, $video, $course)) {
            $this->deny(ErrorCodes::VIDEO_ACCESS_DENIED, 'Video access denied.', 403);
        }
    }

    public function grantFromCode(User $student, VideoAccessCode $code): VideoAccess
    {
        return DB::transaction(function () use ($student, $code): VideoAccess {
            /** @var VideoAccess|null $existingByCode */
            $existingByCode = VideoAccess::query()
                ->withTrashed()
                ->where('video_access_code_id', $code->id)
                ->lockForUpdate()
                ->first();

            if ($existingByCode instanceof VideoAccess) {
                if ($existingByCode->trashed()) {
                    $existingByCode->restore();
                }

                return $existingByCode->fresh() ?? $existingByCode;
            }

            /** @var VideoAccess|null $existing */
            $existing = VideoAccess::query()
                ->withTrashed()
                ->forUserAndVideo($student->id, (int) $code->video_id)
                ->where('course_id', (int) $code->course_id)
                ->lockForUpdate()
                ->first();

            if ($existing instanceof VideoAccess) {
                if ($existing->revoked_at === null && ! $existing->trashed()) {
                    $this->deny(ErrorCodes::VIDEO_ACCESS_ALREADY_GRANTED, 'Video access already granted.', 422);
                }

                if ($existing->trashed()) {
                    $existing->restore();
                }

                $existing->fill([
                    'center_id' => $code->center_id,
                    'enrollment_id' => $code->enrollment_id,
                    'video_access_request_id' => $code->video_access_request_id,
                    'video_access_code_id' => $code->id,
                    'granted_at' => Carbon::now(),
                    'revoked_at' => null,
                    'revoked_by' => null,
                ]);
                $existing->save();

                return $existing->fresh() ?? $existing;
            }

            /** @var VideoAccess $access */
            $access = VideoAccess::query()->create([
                'user_id' => $student->id,
                'video_id' => $code->video_id,
                'course_id' => $code->course_id,
                'center_id' => $code->center_id,
                'enrollment_id' => $code->enrollment_id,
                'video_access_request_id' => $code->video_access_request_id,
                'video_access_code_id' => $code->id,
                'granted_at' => Carbon::now(),
            ]);

            return $access;
        });
    }

    public function revoke(User $admin, VideoAccess $access): VideoAccess
    {
        if ($admin->is_student) {
            $this->deny(ErrorCodes::UNAUTHORIZED, 'Only admins can revoke video access.', 403);
        }

        $this->centerScopeService->assertAdminSameCenter($admin, $access);

        if ($access->revoked_at !== null) {
            return $access;
        }

        $access->revoked_at = Carbon::now();
        $access->revoked_by = $admin->id;
        $access->save();

        return $access->fresh() ?? $access;
    }

    /**
     * @return never
     */
    private function deny(string $code, string $message, int $status): void
    {
        throw new DomainException($message, $code, $status);
    }
}
