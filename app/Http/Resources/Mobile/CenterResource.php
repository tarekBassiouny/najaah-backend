<?php

declare(strict_types=1);

namespace App\Http\Resources\Mobile;

use App\Models\Center;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Center
 */
class CenterResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Center $center */
        $center = $this->resource;

        return [
            'id' => $center->id,
            'slug' => $center->slug,
            'type' => $center->type,
            'name' => $center->translate('name'),
            'is_featured' => $center->is_featured,
            'description' => $center->translate('description'),
            'logo_url' => $center->logo_url,
            'theme' => $center->setting?->settings['theme'] ?? null,
            'courses' => CenterListCourseResource::collection($this->whenLoaded('coursesPreview')),
            'courses_meta' => $this->when(
                $center->offsetExists('courses_meta'),
                fn (): mixed => $center->getAttribute('courses_meta')
            ),
        ];
    }
}
