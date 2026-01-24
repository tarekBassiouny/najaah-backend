<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\VideoUploadStatus;
use App\Models\Concerns\HasTranslations;
use App\Models\Pivots\CourseVideo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $center_id
 * @property array<string, string> $title_translations
 * @property array<string, string>|null $description_translations
 * @property int $source_type
 * @property string $source_provider
 * @property int|null $library_id
 * @property string|null $source_id
 * @property string|null $source_url
 * @property int|null $duration_seconds
 * @property int $lifecycle_status
 * @property array<string, mixed>|null $tags
 * @property int $created_by
 * @property int|null $upload_session_id
 * @property string|null $original_filename
 * @property VideoUploadStatus $encoding_status
 * @property int $views_count
 * @property string|null $thumbnail_url
 * @property array<string, string>|null $thumbnail_urls
 * @property-read User $creator
 * @property-read Center $center
 * @property-read VideoUploadSession|null $uploadSession
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Course> $courses
 * @property-read VideoSetting|null $setting
 * @property-read \Illuminate\Database\Eloquent\Collection<int, PlaybackSession> $playbackSessions
 */
class Video extends Model
{
    /** @use HasFactory<\Database\Factories\VideoFactory> */
    use HasFactory;

    use HasTranslations;
    use SoftDeletes;

    protected $fillable = [
        'center_id',
        'title_translations',
        'description_translations',
        'source_type',
        'source_provider',
        'source_id',
        'source_url',
        'library_id',
        'duration_seconds',
        'lifecycle_status',
        'tags',
        'created_by',
        'upload_session_id',
        'original_filename',
        'encoding_status',
        'is_demo',
        'thumbnail_url',
        'thumbnail_urls',
        'views_count',
    ];

    protected $casts = [
        'center_id' => 'integer',
        'title_translations' => 'array',
        'description_translations' => 'array',
        'tags' => 'array',
        'duration_seconds' => 'integer',
        'lifecycle_status' => 'integer',
        'source_type' => 'integer',
        'encoding_status' => VideoUploadStatus::class,
        'is_demo' => 'boolean',
        'library_id' => 'integer',
        'thumbnail_urls' => 'array',
        'views_count' => 'integer',
    ];

    /** @var array<int, string> */
    protected array $translatable = [
        'title',
        'description',
    ];

    /** @return BelongsTo<User, self> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<Center, self> */
    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    /** @return BelongsTo<VideoUploadSession, self> */
    public function uploadSession(): BelongsTo
    {
        return $this->belongsTo(VideoUploadSession::class, 'upload_session_id');
    }

    /** @return HasOne<VideoSetting, self> */
    public function setting(): HasOne
    {
        return $this->hasOne(VideoSetting::class);
    }

    /** @return BelongsToMany<Course, self> */
    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'course_video')
            ->using(CourseVideo::class)
            ->withPivot(['section_id', 'order_index', 'visible', 'view_limit_override', 'created_at', 'updated_at', 'deleted_at'])
            ->withTimestamps()
            ->wherePivotNull('deleted_at');
    }

    /** @return HasMany<PlaybackSession, self> */
    public function playbackSessions(): HasMany
    {
        return $this->hasMany(PlaybackSession::class);
    }
}
