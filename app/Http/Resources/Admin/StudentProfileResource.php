<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class StudentProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var User $student */
        $student = $this->resource;

        $status = $student->status instanceof UserStatus
            ? $student->status
            : ($student->status !== null ? UserStatus::tryFrom((int) $student->status) : null);

        // Build enrollment resources with context
        $enrollmentResources = $student->enrollments->map(function ($enrollment) use ($student): \App\Http\Resources\Admin\StudentEnrollmentResource {
            return (new StudentEnrollmentResource($enrollment))
                ->setContext($student, $student->playbackSessions);
        });

        // Get active device (first one, already ordered by last_used_at desc)
        $activeDevice = $student->relationLoaded('devices') ? $student->devices->first() : null;

        // Get device change requests (approved ones)
        $deviceChangeRequests = $student->relationLoaded('deviceChangeRequests')
            ? $student->deviceChangeRequests
            : collect();

        // Calculate last activity from active device or playback sessions
        $lastActivityAt = $this->calculateLastActivity($student, $activeDevice);

        return [
            'id' => $student->id,
            'name' => $student->name,
            'username' => $student->username,
            'email' => $student->email,
            'phone' => $student->phone,
            'country_code' => $student->country_code,
            'avatar_url' => $student->avatar_url,
            'status' => $status?->value ?? $student->status,
            'status_label' => $status?->name,

            // Stats card fields
            'last_activity_at' => $lastActivityAt,
            'active_device' => $activeDevice !== null ? [
                'model' => $activeDevice->model,
                'device_id' => $activeDevice->device_id,
            ] : null,
            'total_enrollments' => $student->enrollments->count(),
            'device_changes_count' => $deviceChangeRequests->count(),

            // Device change log
            'device_change_log' => DeviceChangeLogResource::collection($deviceChangeRequests),

            'center' => $this->when($student->relationLoaded('center') && $student->center !== null, fn (): array => [
                'id' => $student->center->id,
                'name' => $student->center->translate('name'),
            ]),
            'enrollments' => $enrollmentResources,
        ];
    }

    /**
     * Calculate last activity timestamp from device or playback sessions.
     */
    private function calculateLastActivity(User $student, mixed $activeDevice): ?string
    {
        $timestamps = [];

        // Add device last_used_at if available
        if ($activeDevice !== null && $activeDevice->last_used_at !== null) {
            $timestamps[] = $activeDevice->last_used_at;
        }

        // Add latest playback session timestamp if available
        if ($student->relationLoaded('playbackSessions') && $student->playbackSessions->isNotEmpty()) {
            $latestSession = $student->playbackSessions->sortByDesc('updated_at')->first();
            if ($latestSession !== null && $latestSession->updated_at !== null) {
                $timestamps[] = $latestSession->updated_at;
            }
        }

        if (empty($timestamps)) {
            return null;
        }

        // Return the most recent timestamp
        $maxTimestamp = max($timestamps);

        return $maxTimestamp->toISOString();
    }
}
