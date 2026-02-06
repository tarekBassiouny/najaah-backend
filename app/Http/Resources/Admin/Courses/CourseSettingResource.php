<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Courses;

use App\Http\Resources\Admin\Summary\CourseSummaryResource;
use App\Models\CourseSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CourseSetting
 */
class CourseSettingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var CourseSetting $setting */
        $setting = $this->resource;

        return [
            'id' => $setting->id,
            'course' => new CourseSummaryResource($this->whenLoaded('course')),
            'settings' => $setting->settings,
            'created_at' => $setting->created_at,
            'updated_at' => $setting->updated_at,
        ];
    }
}
