<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\PlaybackSessions;

use App\Enums\UserDeviceStatus;
use App\Http\Resources\Admin\Summary\CourseSummaryResource;
use App\Http\Resources\Admin\Summary\UserSummaryResource;
use App\Http\Resources\Admin\Summary\VideoSummaryResource;
use App\Models\PlaybackSession;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * @mixin PlaybackSession
 */
final class PlaybackSessionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var PlaybackSession $session */
        $session = $this->resource;

        return [
            'id' => $session->id,
            'user' => new UserSummaryResource($this->whenLoaded('user', $session->user)),
            'video' => new VideoSummaryResource($this->whenLoaded('video', $session->video)),
            'course' => new CourseSummaryResource($this->whenLoaded('course', $session->course)),
            'device' => $this->formatDevice(),
            'started_at' => $session->started_at,
            'ended_at' => $session->ended_at,
            'expires_at' => $session->expires_at,
            'last_activity_at' => $session->last_activity_at,
            'progress_percent' => $session->progress_percent,
            'is_full_play' => $session->is_full_play,
            'is_locked' => $session->is_locked,
            'auto_closed' => $session->auto_closed,
            'watch_duration' => $session->watch_duration,
            'close_reason' => $session->close_reason,
            'embed_token_expires_at' => $session->embed_token_expires_at,
            'is_active' => $session->ended_at === null,
            'created_at' => $session->created_at,
            'updated_at' => $session->updated_at,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function formatDevice(): ?array
    {
        $device = $this->whenLoaded('device', $this->resource->device);

        if ($device === null) {
            return null;
        }

        $status = $device->status instanceof UserDeviceStatus
            ? $device->status
            : (is_numeric($device->status) ? UserDeviceStatus::tryFrom((int) $device->status) : null);

        return [
            'id' => $device->id,
            'device_id' => $device->device_id,
            'device_name' => $device->device_name,
            'device_type' => $device->device_type,
            'status' => $status?->value ?? $device->status,
            'status_key' => $status !== null ? Str::snake($status->name) : null,
            'status_label' => $status?->name,
        ];
    }
}
