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
 * @property array<string, string> $title_translations
 * @property array<string, string>|null $description_translations
 * @property int $source_type
 * @property string $source_provider
 * @property string|null $source_id
 * @property string|null $source_url
 * @property int|null $file_size_kb
 * @property string $file_extension
 * @property int $created_by
 * @property-read User $creator
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Course> $courses
 */
class Pdf extends Model
{
    /** @use HasFactory<\Database\Factories\PdfFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title_translations',
        'description_translations',
        'source_type',
        'source_provider',
        'source_id',
        'source_url',
        'file_size_kb',
        'file_extension',
        'created_by',
    ];

    protected $casts = [
        'title_translations' => 'array',
        'description_translations' => 'array',
        'file_size_kb' => 'integer',
        'source_type' => 'integer',
    ];

    /** @return BelongsTo<User, self> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsToMany<Course, self> */
    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'course_pdf')
            ->withPivot(['section_id', 'video_id', 'order_index', 'visible', 'download_permission_override', 'created_at', 'updated_at', 'deleted_at'])
            ->withTimestamps()
            ->wherePivotNull('deleted_at');
    }
}
