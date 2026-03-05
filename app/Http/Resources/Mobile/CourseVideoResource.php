<?php

declare(strict_types=1);

namespace App\Http\Resources\Mobile;

use App\Enums\VideoAccessCodeStatus;
use App\Enums\VideoAccessRequestStatus;
use App\Models\Course;
use App\Models\Pivots\CourseVideo;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoAccess;
use App\Models\VideoAccessCode;
use App\Models\VideoAccessRequest;
use App\Services\Playback\Contracts\ViewLimitServiceInterface;
use App\Services\VideoAccess\Contracts\VideoApprovalServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Video
 */
class CourseVideoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Video $video */
        $video = $this->resource;
        /** @var CourseVideo|null $pivot */
        $pivot = $video->pivot instanceof CourseVideo ? $video->pivot : null;
        $course = $this->resolveCourse($request, $video, $pivot);

        $requiresRedemption = $this->requiresRedemption($course);
        $redemptionData = $this->resolveRedemptionStatus($request, $video, $course, $requiresRedemption);

        $isLocked = ! $redemptionData['has_redeemed'];

        if (! $isLocked && ! (bool) ($pivot?->visible ?? true)) {
            $isLocked = true;
        }

        if ($pivot !== null && ! $isLocked) {
            $isLocked = $this->isViewLimitExceeded($request, $video, $pivot);
        }

        return [
            'id' => $video->id,
            'title' => $video->translate('title'),
            'tags' => $video->tags,
            'duration' => $video->duration_seconds,
            'duration_seconds' => $video->duration_seconds,
            'thumbnail' => $video->thumbnail_url,
            'thumbnail_url' => $video->thumbnail_url,
            'requires_redemption' => $requiresRedemption,
            'has_redeemed' => $redemptionData['has_redeemed'],
            'is_locked' => $isLocked,
            'access_status' => $redemptionData['access_status'],
            'pending_request_id' => $redemptionData['pending_request_id'],
            'updated_at' => $video->updated_at,
        ];
    }

    private function isViewLimitExceeded(Request $request, Video $video, CourseVideo $pivot): bool
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user === null) {
            return false;
        }

        $course = $pivot->course;

        if ($course === null) {
            return false;
        }

        /** @var ViewLimitServiceInterface $viewLimitService */
        $viewLimitService = app(ViewLimitServiceInterface::class);

        return $viewLimitService->isLocked($user, $video, $course);
    }

    /**
     * @return array{has_redeemed:bool,access_status:?string,pending_request_id:?int}
     */
    private function resolveRedemptionStatus(Request $request, Video $video, ?Course $course, bool $requiresRedemption): array
    {
        /** @var User|null $user */
        $user = $request->user();

        if (! $requiresRedemption) {
            return [
                'has_redeemed' => true,
                'access_status' => null,
                'pending_request_id' => null,
            ];
        }

        if (! $user instanceof User || ! $course instanceof Course) {
            return [
                'has_redeemed' => false,
                'access_status' => 'locked',
                'pending_request_id' => null,
            ];
        }

        $hasRedeemed = VideoAccess::query()
            ->forUserAndVideo($user->id, $video->id)
            ->where('course_id', $course->id)
            ->active()
            ->exists();

        if ($hasRedeemed) {
            return [
                'has_redeemed' => true,
                'access_status' => 'granted',
                'pending_request_id' => null,
            ];
        }

        /** @var VideoAccessRequest|null $pendingRequest */
        $pendingRequest = VideoAccessRequest::query()
            ->where('user_id', $user->id)
            ->where('video_id', $video->id)
            ->where('course_id', $course->id)
            ->where('status', VideoAccessRequestStatus::Pending->value)
            ->orderByDesc('id')
            ->first();

        if ($pendingRequest instanceof VideoAccessRequest) {
            return [
                'has_redeemed' => false,
                'access_status' => 'pending',
                'pending_request_id' => $pendingRequest->id,
            ];
        }

        /** @var VideoAccessCode|null $activeCode */
        $activeCode = VideoAccessCode::query()
            ->where('user_id', $user->id)
            ->where('video_id', $video->id)
            ->where('course_id', $course->id)
            ->where('status', VideoAccessCodeStatus::Active->value)
            ->orderByDesc('id')
            ->first();

        if ($activeCode instanceof VideoAccessCode) {
            if ($activeCode->expires_at !== null && $activeCode->expires_at->isPast()) {
                $activeCode->status = VideoAccessCodeStatus::Expired;
                $activeCode->save();
            } else {
                return [
                    'has_redeemed' => false,
                    'access_status' => 'approved',
                    'pending_request_id' => null,
                ];
            }
        }

        $wasRejected = VideoAccessRequest::query()
            ->where('user_id', $user->id)
            ->where('video_id', $video->id)
            ->where('course_id', $course->id)
            ->where('status', VideoAccessRequestStatus::Rejected->value)
            ->exists();

        if ($wasRejected) {
            return [
                'has_redeemed' => false,
                'access_status' => 'rejected',
                'pending_request_id' => null,
            ];
        }

        return [
            'has_redeemed' => false,
            'access_status' => 'locked',
            'pending_request_id' => null,
        ];
    }

    private function requiresRedemption(?Course $course): bool
    {
        if (! $course instanceof Course) {
            return false;
        }

        if ($course->center === null) {
            return false;
        }

        /** @var VideoApprovalServiceInterface $videoApprovalService */
        $videoApprovalService = app(VideoApprovalServiceInterface::class);

        return $videoApprovalService->requiresApproval($course->center, $course);
    }

    private function resolveCourse(Request $request, Video $video, ?CourseVideo $pivot): ?Course
    {
        $routeCourse = $request->route('course');
        if ($routeCourse instanceof Course) {
            return $routeCourse;
        }

        if ($pivot instanceof CourseVideo && $pivot->relationLoaded('course')) {
            return $pivot->course;
        }

        if ($video->relationLoaded('courses')) {
            /** @var Course|null $course */
            $course = $video->courses->first();

            return $course;
        }

        return null;
    }
}
