<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $center_landing_page_id
 * @property string $author_name
 * @property string|null $author_title
 * @property string|null $author_image_url
 * @property array<string, string> $content_translations
 * @property int|null $rating
 * @property int $sort_order
 * @property bool $is_active
 * @property-read CenterLandingPage $landingPage
 */
class CenterLandingTestimonial extends Model
{
    /** @use HasFactory<\Database\Factories\CenterLandingTestimonialFactory> */
    use HasFactory;

    use HasTranslations;
    use SoftDeletes;

    protected $fillable = [
        'center_landing_page_id',
        'author_name',
        'author_title',
        'author_image_url',
        'content_translations',
        'rating',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'content_translations' => 'array',
        'rating' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    /** @var array<int, string> */
    protected array $translatable = [
        'content',
    ];

    /** @return BelongsTo<CenterLandingPage, self> */
    public function landingPage(): BelongsTo
    {
        return $this->belongsTo(CenterLandingPage::class, 'center_landing_page_id');
    }
}
