<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Sections;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class SectionCollection extends ResourceCollection
{
    public $collects = SectionSummaryResource::class;

    /**
     * @return array<int, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array<int, mixed> $resolved */
        $resolved = SectionSummaryResource::collection($this->collection)->resolve();

        return $resolved;
    }
}
