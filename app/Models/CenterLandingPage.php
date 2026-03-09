<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LandingPageStatus;
use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $center_id
 * @property LandingPageStatus $status
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property string|null $meta_keywords
 * @property array<string, string>|null $hero_title_translations
 * @property array<string, string>|null $hero_subtitle_translations
 * @property string|null $hero_background_url
 * @property string|null $hero_cta_text
 * @property string|null $hero_cta_url
 * @property array<string, string>|null $about_title_translations
 * @property array<string, string>|null $about_content_translations
 * @property string|null $about_image_url
 * @property string|null $contact_email
 * @property string|null $contact_phone
 * @property string|null $contact_address
 * @property string|null $social_facebook
 * @property string|null $social_twitter
 * @property string|null $social_instagram
 * @property string|null $social_youtube
 * @property string|null $social_linkedin
 * @property string|null $social_tiktok
 * @property string|null $primary_color
 * @property string|null $secondary_color
 * @property string|null $font_family
 * @property bool $show_hero
 * @property bool $show_about
 * @property bool $show_courses
 * @property bool $show_testimonials
 * @property bool $show_contact
 * @property-read Center $center
 * @property-read \Illuminate\Database\Eloquent\Collection<int, CenterLandingTestimonial> $testimonials
 */
class CenterLandingPage extends Model
{
    /** @use HasFactory<\Database\Factories\CenterLandingPageFactory> */
    use HasFactory;

    use HasTranslations;
    use SoftDeletes;

    protected $fillable = [
        'center_id',
        'status',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'hero_title_translations',
        'hero_subtitle_translations',
        'hero_background_url',
        'hero_cta_text',
        'hero_cta_url',
        'about_title_translations',
        'about_content_translations',
        'about_image_url',
        'contact_email',
        'contact_phone',
        'contact_address',
        'social_facebook',
        'social_twitter',
        'social_instagram',
        'social_youtube',
        'social_linkedin',
        'social_tiktok',
        'primary_color',
        'secondary_color',
        'font_family',
        'show_hero',
        'show_about',
        'show_courses',
        'show_testimonials',
        'show_contact',
    ];

    protected $casts = [
        'status' => LandingPageStatus::class,
        'hero_title_translations' => 'array',
        'hero_subtitle_translations' => 'array',
        'about_title_translations' => 'array',
        'about_content_translations' => 'array',
        'show_hero' => 'boolean',
        'show_about' => 'boolean',
        'show_courses' => 'boolean',
        'show_testimonials' => 'boolean',
        'show_contact' => 'boolean',
    ];

    /** @var array<int, string> */
    protected array $translatable = [
        'hero_title',
        'hero_subtitle',
        'about_title',
        'about_content',
    ];

    /** @return BelongsTo<Center, self> */
    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    /** @return HasMany<CenterLandingTestimonial, self> */
    public function testimonials(): HasMany
    {
        return $this->hasMany(CenterLandingTestimonial::class)->orderBy('sort_order');
    }

    public function isPublished(): bool
    {
        return $this->status === LandingPageStatus::Published;
    }
}
