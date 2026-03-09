<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\LandingPages;

use App\Models\CenterLandingTestimonial;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CenterLandingTestimonial
 */
class LandingTestimonialResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var CenterLandingTestimonial $testimonial */
        $testimonial = $this->resource;

        return [
            'id' => $testimonial->id,
            'center_landing_page_id' => $testimonial->center_landing_page_id,
            'author_name' => $testimonial->author_name,
            'author_title' => $testimonial->author_title,
            'author_image_url' => $testimonial->author_image_url,
            'content' => $testimonial->translate('content'),
            'content_translations' => $testimonial->content_translations,
            'rating' => $testimonial->rating,
            'sort_order' => $testimonial->sort_order,
            'is_active' => $testimonial->is_active,
            'created_at' => $testimonial->created_at?->toISOString(),
            'updated_at' => $testimonial->updated_at?->toISOString(),
        ];
    }
}
