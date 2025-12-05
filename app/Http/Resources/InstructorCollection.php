<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class InstructorCollection extends ResourceCollection
{
    public $collects = InstructorResource::class;

    /**
     * @param  \Illuminate\Http\Request  $request
     * @return array<int, mixed>
     */
    public function toArray($request): array
    {
        /** @var array<int, mixed> $resolved */
        $resolved = InstructorResource::collection($this->collection)->resolve();

        return $resolved;
    }
}
