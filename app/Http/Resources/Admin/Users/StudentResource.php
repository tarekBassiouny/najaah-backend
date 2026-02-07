<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Users;

use App\Enums\UserStatus;
use App\Http\Resources\Admin\Summary\CenterSummaryResource;
use App\Http\Resources\Mobile\DeviceResource;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * @mixin User
 */
class StudentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var User $user */
        $user = $this->resource;
        $status = $user->status instanceof UserStatus
            ? $user->status
            : ($user->status !== null ? UserStatus::tryFrom((int) $user->status) : null);
        $activeDevice = $user->relationLoaded('devices')
            ? $user->devices->first()
            : null;
        $analytics = $user->getAttribute('analytics_summary');
        $analyticsSummary = is_array($analytics) ? $analytics : [];

        return [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'phone' => $user->phone,
            'country_code' => $user->country_code,
            'center_id' => $user->center_id,
            'status' => $status?->value ?? $user->status,
            'status_key' => $status !== null ? Str::snake($status->name) : null,
            'status_label' => $status?->name,
            'center' => new CenterSummaryResource($this->whenLoaded('center')),
            'device' => $activeDevice instanceof UserDevice
                ? new DeviceResource($activeDevice)
                : null,
            'analytics' => [
                'total_enrollments' => (int) data_get($analyticsSummary, 'total_enrollments', 0),
                'active_enrollments' => (int) data_get($analyticsSummary, 'active_enrollments', 0),
                'total_sessions' => (int) data_get($analyticsSummary, 'total_sessions', 0),
                'full_play_sessions' => (int) data_get($analyticsSummary, 'full_play_sessions', 0),
                'viewed_videos' => (int) data_get($analyticsSummary, 'viewed_videos', 0),
                'last_activity_at' => data_get($analyticsSummary, 'last_activity_at'),
            ],
        ];
    }
}
