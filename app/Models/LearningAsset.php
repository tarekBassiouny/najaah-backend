<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LearningAssetStatus;
use App\Enums\LearningAssetType;
use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $center_id
 * @property int $course_id
 * @property string|null $attachable_type
 * @property int|null $attachable_id
 * @property LearningAssetType $asset_type
 * @property LearningAssetStatus $status
 * @property array<string,string>|null $title_translations
 * @property array<string,string>|null $content_translations
 * @property array<string,mixed>|null $payload
 * @property bool $is_active
 * @property int $created_by
 * @property int|null $updated_by
 * @property int|null $published_by
 * @property \Carbon\Carbon|null $published_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property-read Center $center
 * @property-read Course $course
 * @property-read User $creator
 * @property-read User|null $updater
 * @property-read User|null $publisher
 * @property-read Model|null $attachable
 */
class LearningAsset extends Model
{
    /** @use HasFactory<\Database\Factories\LearningAssetFactory> */
    use HasFactory;

    use HasTranslations;
    use SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'center_id',
        'course_id',
        'attachable_type',
        'attachable_id',
        'asset_type',
        'status',
        'title_translations',
        'content_translations',
        'payload',
        'is_active',
        'created_by',
        'updated_by',
        'published_by',
        'published_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'asset_type' => LearningAssetType::class,
        'status' => LearningAssetStatus::class,
        'title_translations' => 'array',
        'content_translations' => 'array',
        'payload' => 'array',
        'is_active' => 'boolean',
        'published_at' => 'datetime',
    ];

    /** @var array<int, string> */
    protected array $translatable = [
        'title',
        'content',
    ];

    /** @return BelongsTo<Center, self> */
    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    /** @return BelongsTo<Course, self> */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /** @return BelongsTo<User, self> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, self> */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /** @return BelongsTo<User, self> */
    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    /** @return MorphTo<Model, self> */
    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return HasMany<LearningAssetProgress, self> */
    public function progressRecords(): HasMany
    {
        return $this->hasMany(LearningAssetProgress::class);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', LearningAssetStatus::Published)
            ->where('is_active', true);
    }
}
