<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use App\Http\Resources\Admin\Summary\CenterSummaryResource;
use App\Http\Resources\Admin\Summary\CourseSummaryResource;
use App\Http\Resources\Admin\Summary\UserSummaryResource;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * @mixin Enrollment
 */
class EnrollmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Enrollment $enrollment */
        $enrollment = $this->resource;

        return [
            'id' => $enrollment->id,
            'status' => $enrollment->statusLabel(),
            'status_value' => $enrollment->status->value,
            'status_key' => Str::snake($enrollment->status->name),
            'status_label' => $enrollment->status->name,
            'user_id' => $enrollment->user_id,
            'course_id' => $enrollment->course_id,
            'center_id' => $enrollment->center_id,
            'user' => new UserSummaryResource($this->whenLoaded('user')),
            'student' => new UserSummaryResource($this->whenLoaded('user')),
            'course' => new CourseSummaryResource($this->whenLoaded('course')),
            'center' => new CenterSummaryResource($this->whenLoaded('center')),
            'enrolled_at' => $enrollment->enrolled_at,
            'expires_at' => $enrollment->expires_at,
        ];
    }
}
