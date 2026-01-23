<?php

declare(strict_types=1);

namespace App\Http\Resources\Mobile;

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
            'name' => $instructor->translate('name'),
            'title' => $instructor->translate('title'),
            'avatar_url' => $avatarUrl,
        ];
    }
}
