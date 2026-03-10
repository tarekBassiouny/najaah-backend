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
 * @property array<int, string>|null $section_order
 * @property array<string, string>|null $section_layouts
 * @property array<string, array<string, scalar|null>>|null $section_styles
 * @property-read Center $center
 * @property-read \Illuminate\Database\Eloquent\Collection<int, CenterLandingTestimonial> $testimonials
 */
class CenterLandingPage extends Model
{
    /** @use HasFactory<\Database\Factories\CenterLandingPageFactory> */
    use HasFactory;

    use HasTranslations;
    use SoftDeletes;

    public const SECTION_HERO = 'hero';

    public const SECTION_ABOUT = 'about';

    public const SECTION_COURSES = 'courses';

    public const SECTION_TESTIMONIALS = 'testimonials';

    public const SECTION_CONTACT = 'contact';

    /** @var array<int, string> */
    public const DEFAULT_SECTION_ORDER = [
        self::SECTION_HERO,
        self::SECTION_ABOUT,
        self::SECTION_COURSES,
        self::SECTION_TESTIMONIALS,
        self::SECTION_CONTACT,
    ];

    /** @var array<string, array<int, string>> */
    public const ALLOWED_LAYOUT_VARIANTS = [
        self::SECTION_HERO => ['default', 'split'],
        self::SECTION_ABOUT => ['default', 'split'],
        self::SECTION_COURSES => ['default', 'grid'],
        self::SECTION_TESTIMONIALS => ['default', 'cards'],
        self::SECTION_CONTACT => ['default', 'split'],
    ];

    /** @var array<string, array<int, string>> */
    public const ALLOWED_STYLE_KEYS = [
        self::SECTION_HERO => ['text_align', 'overlay_opacity', 'content_width'],
        self::SECTION_ABOUT => ['text_align', 'image_fit'],
        self::SECTION_COURSES => ['columns_desktop', 'columns_mobile'],
        self::SECTION_TESTIMONIALS => ['card_style', 'columns_desktop'],
        self::SECTION_CONTACT => ['layout', 'show_map'],
    ];

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
        'section_order',
        'section_layouts',
        'section_styles',
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
        'section_order' => 'array',
        'section_layouts' => 'array',
        'section_styles' => 'array',
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

    /**
     * @return array<int, string>
     */
    public function effectiveSectionOrder(): array
    {
        $order = is_array($this->section_order) ? array_values($this->section_order) : [];
        if ($order === []) {
            return self::DEFAULT_SECTION_ORDER;
        }

        return $order;
    }

    /**
     * @return array<string, string>
     */
    public function effectiveSectionLayouts(): array
    {
        $stored = is_array($this->section_layouts) ? $this->section_layouts : [];
        $defaults = self::defaultSectionLayouts();

        foreach ($defaults as $section => $variant) {
            if (! isset($stored[$section]) || ! is_string($stored[$section]) || $stored[$section] === '') {
                $stored[$section] = $variant;
            }
        }

        return $stored;
    }

    /**
     * @return array<string, array<string, scalar|null>>
     */
    public function effectiveSectionStyles(): array
    {
        $stored = is_array($this->section_styles) ? $this->section_styles : [];
        $defaults = self::defaultSectionStyles();

        foreach ($defaults as $section => $styles) {
            if (! isset($stored[$section]) || ! is_array($stored[$section])) {
                $stored[$section] = $styles;
            }
        }

        return $stored;
    }

    /**
     * @return array<string, string>
     */
    public static function defaultSectionLayouts(): array
    {
        return [
            self::SECTION_HERO => 'default',
            self::SECTION_ABOUT => 'default',
            self::SECTION_COURSES => 'default',
            self::SECTION_TESTIMONIALS => 'default',
            self::SECTION_CONTACT => 'default',
        ];
    }

    /**
     * @return array<string, array<string, scalar|null>>
     */
    public static function defaultSectionStyles(): array
    {
        return [
            self::SECTION_HERO => [],
            self::SECTION_ABOUT => [],
            self::SECTION_COURSES => [],
            self::SECTION_TESTIMONIALS => [],
            self::SECTION_CONTACT => [],
        ];
    }
}
