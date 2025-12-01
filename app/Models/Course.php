<?php

declare(strict_types=1);

namespace App\Models;

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
 * @property int|null $category_id
 * @property array<string, string> $title_translations
 * @property array<string, string>|null $description_translations
 * @property array<string, string>|null $instructor_translations
 * @property array<string, string>|null $college_translations
 * @property string|null $grade_year
 * @property int $difficulty_level
 * @property string $language
 * @property string|null $course_code
 * @property array<string, mixed>|null $tags
 * @property int $status
 * @property bool $is_published
 * @property string|null $thumbnail_url
 * @property int|null $duration_minutes
 * @property bool $is_featured
 * @property int $created_by
 * @property int|null $cloned_from_id
 * @property \Carbon\Carbon|null $publish_at
 * @property-read Center $center
 * @property-read Category|null $category
 * @property-read User $creator
 * @property-read CourseSetting|null $setting
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Section> $sections
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Video> $videos
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Pdf> $pdfs
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Enrollment> $enrollments
 */
class Course extends Model
{
    /** @use HasFactory<\Database\Factories\CourseFactory> */
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'center_id',
        'category_id',
        'title_translations',
        'description_translations',
        'instructor_translations',
        'college_translations',
        'grade_year',
        'thumbnail_url',
        'difficulty_level',
        'language',
        'course_code',
        'tags',
        'status',
        'is_published',
        'duration_minutes',
        'is_featured',
        'created_by',
        'cloned_from_id',
        'publish_at',
    ];

    protected $casts = [
        'title_translations' => 'array',
        'description_translations' => 'array',
        'instructor_translations' => 'array',
        'college_translations' => 'array',
        'tags' => 'array',
        'is_published' => 'boolean',
        'is_featured' => 'boolean',
        'duration_minutes' => 'integer',
        'difficulty_level' => 'integer',
        'status' => 'integer',
        'publish_at' => 'datetime',
    ];

    /** @return BelongsTo<Center, self> */
    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    /** @return BelongsTo<Category, self> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return BelongsTo<User, self> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<Course, self> */
    public function clonedFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'cloned_from_id');
    }

    /** @return HasOne<CourseSetting, self> */
    public function setting(): HasOne
    {
        return $this->hasOne(CourseSetting::class);
    }

    /** @return HasMany<Section, self> */
    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }

    /**
     * @return BelongsToMany<Video, self>
     */
    public function videos(): BelongsToMany
    {
        return $this->belongsToMany(Video::class, 'course_video')
            ->withPivot(['section_id', 'order_index', 'visible', 'view_limit_override', 'created_at', 'updated_at', 'deleted_at'])
            ->withTimestamps()
            ->wherePivotNull('deleted_at');
    }

    /** @return BelongsToMany<Pdf, self> */
    public function pdfs(): BelongsToMany
    {
        return $this->belongsToMany(Pdf::class, 'course_pdf')
            ->withPivot(['section_id', 'video_id', 'order_index', 'visible', 'download_permission_override', 'created_at', 'updated_at', 'deleted_at'])
            ->withTimestamps()
            ->wherePivotNull('deleted_at');
    }

    /** @return HasMany<Enrollment, self> */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }
}
