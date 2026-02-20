<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CenterStatus;
use App\Enums\CenterTier;
use App\Enums\CenterType;
use App\Models\Concerns\HasTranslatableSearch;
use App\Models\Concerns\HasTranslations;
use App\Models\Pivots\UserCenter;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use RuntimeException;

/**
 * @property int $id
 * @property string $slug
 * @property CenterType $type
 * @property CenterTier $tier
 * @property array<string, string> $name_translations
 * @property array<string, string>|null $description_translations
 * @property string|null $logo_url
 * @property string|null $primary_color
 * @property string $onboarding_status
 * @property array<string, mixed>|null $branding_metadata
 * @property string $storage_driver
 * @property string|null $storage_root
 * @property CenterStatus $status
 * @property int $default_view_limit
 * @property bool $allow_extra_view_requests
 * @property bool $pdf_download_permission
 * @property int $device_limit
 * @property-read \Illuminate\Database\Eloquent\Collection<int, User> $users
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Course> $courses
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Enrollment> $enrollments
 * @property-read CenterSetting|null $setting
 * @property-read \Illuminate\Database\Eloquent\Collection<int, VideoUploadSession> $videoUploadSessions
 */
class Center extends Model
{
    public const ONBOARDING_DRAFT = 'DRAFT';

    public const ONBOARDING_IN_PROGRESS = 'IN_PROGRESS';

    public const ONBOARDING_FAILED = 'FAILED';

    public const ONBOARDING_ACTIVE = 'ACTIVE';

    public const TIER_STANDARD = CenterTier::Standard;

    public const TIER_PREMIUM = CenterTier::Premium;

    public const TIER_VIP = CenterTier::Vip;

    public const TYPE_UNBRANDED = CenterType::Unbranded;

    public const TYPE_BRANDED = CenterType::Branded;

    public const STATUS_INACTIVE = CenterStatus::Inactive;

    public const STATUS_ACTIVE = CenterStatus::Active;

    /** @use HasFactory<\Database\Factories\CenterFactory> */
    use HasFactory;

    use HasTranslatableSearch;
    use HasTranslations;
    use SoftDeletes;

    protected static function booted(): void
    {
        static::creating(function (self $center): void {
            if (! is_string($center->api_key) || trim($center->api_key) === '') {
                $center->api_key = self::generateUniqueApiKey();
            }
        });
    }

    protected $fillable = [
        'slug',
        'api_key',
        'type',
        'tier',
        'is_featured',
        'is_demo',
        'status',
        'name_translations',
        'description_translations',
        'logo_url',
        'primary_color',
        'onboarding_status',
        'branding_metadata',
        'storage_driver',
        'storage_root',
        'default_view_limit',
        'allow_extra_view_requests',
        'pdf_download_permission',
        'device_limit',
    ];

    protected $casts = [
        'name_translations' => 'array',
        'description_translations' => 'array',
        'onboarding_status' => 'string',
        'branding_metadata' => 'array',
        'storage_driver' => 'string',
        'storage_root' => 'string',
        'type' => CenterType::class,
        'tier' => CenterTier::class,
        'is_featured' => 'boolean',
        'is_demo' => 'boolean',
        'status' => CenterStatus::class,
        'allow_extra_view_requests' => 'boolean',
        'pdf_download_permission' => 'boolean',
        'default_view_limit' => 'integer',
        'device_limit' => 'integer',
    ];

    /** @var array<int, string> */
    protected array $translatable = [
        'name',
        'description',
    ];

    /** @return BelongsToMany<User, self> */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_centers')
            ->using(UserCenter::class)
            ->withTimestamps()
            ->withPivot(['type'])
            ->wherePivotNull('deleted_at');
    }

    /** @return HasMany<Course, self> */
    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    /** @return HasMany<Enrollment, self> */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /** @return HasOne<CenterSetting, self> */
    public function setting(): HasOne
    {
        return $this->hasOne(CenterSetting::class);
    }

    /** @return HasMany<VideoUploadSession, self> */
    public function videoUploadSessions(): HasMany
    {
        return $this->hasMany(VideoUploadSession::class);
    }

    public function getStorageRootAttribute(?string $value): string
    {
        if (is_string($value) && $value !== '') {
            return $value;
        }

        return 'centers/'.$this->id;
    }

    public static function generateUniqueApiKey(): string
    {
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $key = bin2hex(random_bytes(20));

            if (! self::query()->where('api_key', $key)->exists()) {
                return $key;
            }
        }

        throw new RuntimeException('Failed to generate unique center API key.');
    }
}
