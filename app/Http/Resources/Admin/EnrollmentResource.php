<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use App\Http\Resources\Admin\Courses\CourseSummaryResource;
use App\Http\Resources\Admin\Users\StudentResource;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'user_id' => $enrollment->user_id,
            'course_id' => $enrollment->course_id,
            'center_id' => $enrollment->center_id,
            'enrolled_at' => $enrollment->enrolled_at,
            'expires_at' => $enrollment->expires_at,
            'course' => new CourseSummaryResource($this->whenLoaded('course')),
            'student' => new StudentResource($this->whenLoaded('user')),
        ];
    }
}
