<?php

declare(strict_types=1);

namespace App\Models\Pivots;

use App\Models\Course;
use App\Models\Section;
use App\Models\Video;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

class CourseVideo extends Pivot
{
    use SoftDeletes;

    protected $table = 'course_video';

    public $incrementing = true;

    public $timestamps = true;

    protected $fillable = [
        'course_id',
        'video_id',
        'section_id',
        'order_index',
        'visible',
        'view_limit_override',
    ];

    protected $casts = [
        'order_index' => 'integer',
        'visible' => 'boolean',
        'view_limit_override' => 'integer',
    ];

    /** @return BelongsTo<Course, self> */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /** @return BelongsTo<Video, self> */
    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }

    /** @return BelongsTo<Section, self> */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }
}
