<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Courses;

use App\Http\Resources\Admin\Videos\VideoResource;
use App\Models\Pivots\CourseVideo;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CourseVideo
 */
class CourseVideoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var CourseVideo $pivot */
        $pivot = $this->resource;

        return [
            'id' => $pivot->id,
            'video_id' => $pivot->video_id,
            'order_index' => $pivot->order_index,
            'visible' => $pivot->visible,
            'view_limit_override' => $pivot->view_limit_override,
            'video' => new VideoResource($this->whenLoaded('video')),
        ];
    }
}
