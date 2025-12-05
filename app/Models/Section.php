<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $course_id
 * @property array<string, string> $title_translations
 * @property array<string, string>|null $description_translations
 * @property int $order_index
 * @property bool $visible
 * @property-read Course $course
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Video> $videos
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Pdf> $pdfs
 */
class Section extends Model
{
    /** @use HasFactory<\Database\Factories\SectionFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'course_id',
        'title_translations',
        'description_translations',
        'order_index',
        'visible',
    ];

    protected $casts = [
        'title_translations' => 'array',
        'description_translations' => 'array',
        'order_index' => 'integer',
        'visible' => 'boolean',
    ];

    /** @return BelongsTo<Course, self> */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /** @return BelongsToMany<Video, self> */
    public function videos(): BelongsToMany
    {
        return $this->belongsToMany(Video::class, 'course_video', 'section_id', 'video_id')
            ->withPivot(['course_id', 'order_index', 'visible', 'view_limit_override', 'created_at', 'updated_at', 'deleted_at'])
            ->withTimestamps()
            ->wherePivotNull('deleted_at');
    }

    /** @return BelongsToMany<Pdf, self> */
    public function pdfs(): BelongsToMany
    {
        return $this->belongsToMany(Pdf::class, 'course_pdf', 'section_id', 'pdf_id')
            ->withPivot(['course_id', 'video_id', 'order_index', 'visible', 'download_permission_override', 'created_at', 'updated_at', 'deleted_at'])
            ->withTimestamps()
            ->wherePivotNull('deleted_at');
    }
}
