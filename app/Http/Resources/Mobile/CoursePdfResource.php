<?php

declare(strict_types=1);

namespace App\Http\Resources\Mobile;

use App\Models\Pdf;
use App\Models\Pivots\CoursePdf;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Pdf
 */
class CoursePdfResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Pdf $pdf */
        $pdf = $this->resource;
        /** @var CoursePdf|null $pivot */
        $pivot = $pdf->pivot instanceof CoursePdf ? $pdf->pivot : null;

        return [
            'id' => $pdf->id,
            'title' => $pdf->translate('title'),
            'pages' => null,
            'is_locked' => ! (bool) ($pivot?->visible ?? true),
        ];
    }
}
