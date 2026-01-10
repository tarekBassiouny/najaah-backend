<?php

declare(strict_types=1);

namespace App\Http\Resources\Mobile;

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
            'name' => $instructor->translate('name'),
            'title' => $instructor->translate('title'),
            'avatar_url' => $instructor->avatar_url,
        ];
    }
}
