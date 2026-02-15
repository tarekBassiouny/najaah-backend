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
            'center' => $this->when($student->relationLoaded('center') && $student->center !== null, fn (): array => [
                'id' => $student->center->id,
                'name' => $student->center->translate('name'),
            ]),
            'enrollments' => $enrollmentResources,
        ];
    }
}
