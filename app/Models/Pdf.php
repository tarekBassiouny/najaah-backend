<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MediaSourceType;
use App\Models\Concerns\HasTranslations;
use App\Models\Pivots\CoursePdf;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $center_id
 * @property array<string, string> $title_translations
 * @property array<string, string>|null $description_translations
 * @property array<string, mixed>|null $tags
 * @property int $source_type
 * @property string $source_provider
 * @property string|null $source_id
 * @property string|null $source_url
 * @property int|null $file_size_kb
 * @property string $file_extension
 * @property int|null $upload_session_id
 * @property int $created_by
 * @property-read User $creator
 * @property-read Center $center
 * @property-read PdfUploadSession|null $uploadSession
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Course> $courses
 */
class Pdf extends Model
{
    /** @use HasFactory<\Database\Factories\PdfFactory> */
    use HasFactory;

    use HasTranslations;
    use SoftDeletes;

    protected $fillable = [
        'center_id',
        'title_translations',
        'description_translations',
        'tags',
        'source_type',
        'source_provider',
        'source_id',
        'source_url',
        'file_size_kb',
        'file_extension',
        'upload_session_id',
        'is_demo',
        'created_by',
    ];

    protected $casts = [
        'center_id' => 'integer',
        'title_translations' => 'array',
        'description_translations' => 'array',
        'tags' => 'array',
        'file_size_kb' => 'integer',
        'source_type' => MediaSourceType::class,
        'is_demo' => 'boolean',
        'upload_session_id' => 'integer',
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

    /** @return BelongsTo<PdfUploadSession, self> */
    public function uploadSession(): BelongsTo
    {
        return $this->belongsTo(PdfUploadSession::class, 'upload_session_id');
    }

    /** @return BelongsToMany<Course, self> */
    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'course_pdf')
            ->using(CoursePdf::class)
            ->withPivot(['section_id', 'video_id', 'order_index', 'visible', 'created_at', 'updated_at', 'deleted_at'])
            ->withTimestamps()
            ->wherePivotNull('deleted_at');
    }

    /** @return BelongsToMany<Section, self> */
    public function sections(): BelongsToMany
    {
        return $this->belongsToMany(Section::class, 'course_pdf')
            ->using(CoursePdf::class)
            ->withPivot(['course_id', 'video_id', 'order_index', 'visible', 'created_at', 'updated_at', 'deleted_at'])
            ->withTimestamps()
            ->wherePivotNull('deleted_at')
            ->wherePivotNotNull('section_id');
    }

    /** @return HasMany<CoursePdf, self> */
    public function coursePdfs(): HasMany
    {
        return $this->hasMany(CoursePdf::class, 'pdf_id')
            ->whereNull('deleted_at');
    }
}
