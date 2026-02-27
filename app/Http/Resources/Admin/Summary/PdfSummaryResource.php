<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Summary;

use App\Models\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Lightweight PDF representation for embedding in other resources.
 * MUST remain flat - no nested relations allowed.
 *
 * @mixin Pdf
 */
class PdfSummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Pdf $pdf */
        $pdf = $this->resource;

        return [
            'id' => $pdf->id,
            'title' => $pdf->translate('title'),
            'tags' => $pdf->tags,
        ];
    }
}
