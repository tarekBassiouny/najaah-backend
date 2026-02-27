<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use App\Http\Resources\Admin\Summary\CenterSummaryResource;
use App\Http\Resources\Admin\Summary\UserSummaryResource;
use App\Models\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Pdf
 */
class PdfResource extends JsonResource
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
            'center' => new CenterSummaryResource($this->whenLoaded('center')),
            'title' => $pdf->translate('title'),
            'title_translations' => $pdf->title_translations,
            'description' => $pdf->translate('description'),
            'description_translations' => $pdf->description_translations,
            'source_type' => $pdf->source_type,
            'source_provider' => $pdf->source_provider,
            'source_id' => $pdf->source_id,
            'source_label' => $pdf->source_type,
            'file_extension' => $pdf->file_extension,
            'file_size_kb' => $pdf->file_size_kb,
            'creator' => new UserSummaryResource($this->whenLoaded('creator')),
            'created_at' => $pdf->created_at,
            'upload_status' => $this->when(
                $this->relationLoaded('uploadSession'),
                fn () => $pdf->uploadSession?->upload_status?->value
            ),
            'upload_status_label' => $this->when(
                $this->relationLoaded('uploadSession'),
                fn () => $pdf->uploadSession?->upload_status?->label()
            ),
            'courses_count' => $this->whenCounted('courses'),
            'sections_count' => $this->whenCounted('sections'),
            'can_delete' => $this->when(
                $request->routeIs('*.index') && isset($pdf->courses_count),
                fn (): bool => ($pdf->courses_count ?? 0) === 0
            ),
        ];
    }
}
