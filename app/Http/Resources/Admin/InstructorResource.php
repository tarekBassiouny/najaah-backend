<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use App\Models\Instructor;
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

        return [
            'id' => $instructor->id,
            'center_id' => $instructor->center_id,
            'name' => $instructor->translate('name'),
            'title' => $instructor->translate('title'),
            'bio' => $instructor->translate('bio'),
            'avatar_url' => $instructor->avatar_url,
            'email' => $instructor->email,
            'phone' => $instructor->phone,
            'social_links' => $instructor->social_links,
            'metadata' => $instructor->metadata,
        ];
    }
}
