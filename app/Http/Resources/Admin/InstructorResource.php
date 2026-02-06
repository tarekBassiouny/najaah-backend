<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use App\Http\Resources\Admin\Summary\CenterSummaryResource;
use App\Http\Resources\Admin\Summary\CourseSummaryResource;
use App\Http\Resources\Admin\Summary\UserSummaryResource;
use App\Models\Instructor;
use App\Services\Storage\Contracts\StorageServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Instructor
 */
class InstructorResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Instructor $instructor */
        $instructor = $this->resource;
        $avatarUrl = $instructor->avatar_url;

        if (is_string($avatarUrl) && $avatarUrl !== '' && ! str_starts_with($avatarUrl, 'http')) {
            $avatarUrl = app(StorageServiceInterface::class)->url($avatarUrl);
        }

        return [
            'id' => $instructor->id,
            'center' => new CenterSummaryResource($this->whenLoaded('center')),
            'name' => $instructor->translate('name'),
            'title' => $instructor->translate('title'),
            'bio' => $instructor->translate('bio'),
            'name_translations' => $instructor->name_translations,
            'title_translations' => $instructor->title_translations,
            'bio_translations' => $instructor->bio_translations,
            'avatar_url' => $avatarUrl,
            'email' => $instructor->email,
            'phone' => $instructor->phone,
            'social_links' => $instructor->social_links,
            'metadata' => $instructor->metadata,
            'creator' => new UserSummaryResource($this->whenLoaded('creator')),
            'courses' => CourseSummaryResource::collection($this->whenLoaded('courses')),
            'courses_count' => $this->when($instructor->courses_count !== null, $instructor->courses_count),
            'created_at' => $instructor->created_at,
            'updated_at' => $instructor->updated_at,
        ];
    }
}
