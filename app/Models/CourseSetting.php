<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $course_id
 * @property array<mixed> $settings
 * @property-read Course $course
 */
class CourseSetting extends Model
{
    /** @use HasFactory<\Database\Factories\CourseSettingFactory> */
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'course_id',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
    ];

    /** @return BelongsTo<Course, self> */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
