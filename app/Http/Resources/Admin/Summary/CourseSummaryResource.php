<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Summary;

use App\Http\Resources\Concerns\ResolvesCourseRequiresVideoApproval;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Lightweight course representation for embedding in other resources.
 * MUST remain flat - no nested relations allowed.
 *
 * @mixin Course
 */
class CourseSummaryResource extends JsonResource
{
    use ResolvesCourseRequiresVideoApproval;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Course $course */
        $course = $this->resource;

        return [
            'id' => $course->id,
            'title' => $course->translate('title'),
            'requires_video_approval' => $this->resolveRequiresVideoApproval($course),
        ];
    }
}
