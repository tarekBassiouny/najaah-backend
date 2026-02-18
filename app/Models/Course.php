<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CenterType;
use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Models\Concerns\HasTranslatableSearch;
use App\Models\Concerns\HasTranslations;
use App\Models\Pivots\CourseInstructor;
use App\Models\Pivots\CoursePdf;
use App\Models\Pivots\CourseVideo;
use Illuminate\Database\Eloquent\Builder;
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
 * @property array<string, string>|null $college_translations
 * @property string|null $grade_year
 * @property int $difficulty_level
 * @property string $language
 * @property string|null $course_code
 * @property int|null $primary_instructor_id
 * @property array<string, mixed>|null $tags
 * @property CourseStatus $status
 * @property bool $is_published
 * @property string|null $thumbnail_url
 * @property int|null $duration_minutes
 * @property bool $is_featured
 * @property int $created_by
 * @property int|null $cloned_from_id
 * @property \Carbon\Carbon|null $publish_at
 * @property-read Center $center
 * @property-read Category|null $category
 * @property-read Instructor|null $primaryInstructor
 * @property-read User $creator
 * @property-read CourseSetting|null $setting
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Section> $sections
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Video> $videos
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Pdf> $pdfs
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Enrollment> $enrollments
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Instructor> $instructors
 */
class Course extends Model
{
    public const STATUS_DRAFT = CourseStatus::Draft;

    public const STATUS_PUBLISHED = CourseStatus::Published;

    /** @use HasFactory<\Database\Factories\CourseFactory> */
    use HasFactory;

    use HasTranslatableSearch;
    use HasTranslations;
    use SoftDeletes;

    protected $with = ['instructors', 'primaryInstructor'];

    protected $fillable = [
        'center_id',
        'category_id',
        'title_translations',
        'description_translations',
        'college_translations',
        'grade_year',
        'thumbnail_url',
        'difficulty_level',
        'language',
        'course_code',
        'primary_instructor_id',
        'tags',
        'status',
        'is_published',
        'duration_minutes',
        'is_featured',
        'is_demo',
        'created_by',
        'cloned_from_id',
        'publish_at',
    ];

    protected $casts = [
        'title_translations' => 'array',
        'description_translations' => 'array',
        'college_translations' => 'array',
        'tags' => 'array',
        'is_published' => 'boolean',
        'is_featured' => 'boolean',
        'is_demo' => 'boolean',
        'duration_minutes' => 'integer',
        'difficulty_level' => 'integer',
        'status' => CourseStatus::class,
        'publish_at' => 'datetime',
        'primary_instructor_id' => 'integer',
    ];

    /** @var array<int, string> */
    protected array $translatable = [
        'title',
        'description',
        'college',
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

    /** @return BelongsTo<Instructor, self> */
    public function primaryInstructor(): BelongsTo
    {
        return $this->belongsTo(Instructor::class, 'primary_instructor_id');
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
            ->using(CourseVideo::class)
            ->withPivot(['section_id', 'order_index', 'visible', 'view_limit_override', 'created_at', 'updated_at', 'deleted_at'])
            ->withTimestamps()
            ->wherePivotNull('deleted_at');
    }

    /** @return BelongsToMany<Pdf, self> */
    public function pdfs(): BelongsToMany
    {
        return $this->belongsToMany(Pdf::class, 'course_pdf')
            ->using(CoursePdf::class)
            ->withPivot(['section_id', 'video_id', 'order_index', 'visible', 'created_at', 'updated_at', 'deleted_at'])
            ->withTimestamps()
            ->wherePivotNull('deleted_at');
    }

    /** @return BelongsToMany<Instructor, self> */
    public function instructors(): BelongsToMany
    {
        return $this->belongsToMany(Instructor::class, 'course_instructors')
            ->using(CourseInstructor::class)
            ->withPivot(['role', 'created_at', 'updated_at', 'deleted_at'])
            ->withTimestamps()
            ->wherePivotNull('deleted_at');
    }

    /** @return HasMany<Enrollment, self> */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /**
     * Scope to filter published courses.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED->value)
            ->where('is_published', true);
    }

    /**
     * Scope to filter courses visible to a student.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeVisibleToStudent(Builder $query, User $student): Builder
    {
        if (is_numeric($student->center_id)) {
            return $query
                ->where('center_id', (int) $student->center_id)
                ->whereHas('center', function ($query): void {
                    $query->where('status', Center::STATUS_ACTIVE->value);
                });
        }

        return $query->whereHas('center', function ($query): void {
            $query
                ->where('type', CenterType::Unbranded->value)
                ->where('status', Center::STATUS_ACTIVE->value);
        });
    }

    /**
     * Scope to filter courses a student is actively enrolled in.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeEnrolledBy(Builder $query, User $student): Builder
    {
        return $query->whereHas('enrollments', function ($query) use ($student): void {
            $query->where('user_id', $student->id)
                ->where('status', EnrollmentStatus::Active->value)
                ->whereNull('deleted_at');
        });
    }

    /**
     * Scope to filter courses a student is not actively enrolled in.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeNotEnrolledBy(Builder $query, User $student): Builder
    {
        return $query->whereDoesntHave('enrollments', function ($query) use ($student): void {
            $query->where('user_id', $student->id)
                ->where('status', EnrollmentStatus::Active->value)
                ->whereNull('deleted_at');
        });
    }

    /**
     * Scope to attach enrollment metadata for a given student.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeWithEnrollmentMeta(Builder $query, User $student, bool $includeStatus = false): Builder
    {
        $query->withExists([
            'enrollments as is_enrolled' => function ($query) use ($student): void {
                $query->where('user_id', $student->id)
                    ->where('status', EnrollmentStatus::Active->value)
                    ->whereNull('deleted_at');
            },
        ]);

        if (! $includeStatus) {
            return $query;
        }

        return $query->addSelect([
            'active_enrollment_status' => Enrollment::select('status')
                ->whereColumn('course_id', 'courses.id')
                ->where('user_id', $student->id)
                ->where('status', EnrollmentStatus::Active->value)
                ->whereNull('deleted_at')
                ->orderByDesc('created_at')
                ->limit(1),
            'latest_enrollment_status' => Enrollment::select('status')
                ->whereColumn('course_id', 'courses.id')
                ->where('user_id', $student->id)
                ->whereNull('deleted_at')
                ->orderByDesc('created_at')
                ->limit(1),
        ]);
    }
}
